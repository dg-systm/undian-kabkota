<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;

/**
 * Definisi stored procedure getWinner.
 *
 * Signature SP (harus sama dengan database undian_samsat):
 *   getWinner(@id_lokasi_samsat INT, @id_kecamatan VARCHAR(10), @length INT = 5)
 *
 * - id_lokasi_samsat = 999  -> mode global (Grand Prize), id_kecamatan diabaikan.
 * - id_kecamatan = ''       -> semua kecamatan pada Samsat tersebut.
 */
class GetWinnerProcedure
{
    public static function create(?string $driver = null): void
    {
        $driver ??= DB::connection()->getDriverName();

        DB::unprepared(match ($driver) {
            'sqlsrv' => static::sqlServer(),
            default  => static::mySql(),
        });
    }

    public static function drop(?string $driver = null): void
    {
        $driver ??= DB::connection()->getDriverName();

        if ($driver === 'sqlsrv') {
            DB::unprepared("IF OBJECT_ID('dbo.getWinner', 'P') IS NOT NULL DROP PROCEDURE dbo.getWinner");

            return;
        }

        DB::unprepared('DROP PROCEDURE IF EXISTS getWinner');
    }

    public static function sqlServer(): string
    {
        return "
            CREATE PROCEDURE [dbo].[getWinner]
                @id_lokasi_samsat INT
                ,@id_kecamatan VARCHAR(10)
                ,@length INT = 5
            AS
            BEGIN
                SET NOCOUNT ON;

                IF @id_lokasi_samsat = 999
                    BEGIN
                        SELECT TOP (@length)
                            [id]
                            ,[id_kendaraan]
                            ,[no_polisi]
                            ,roda
                            ,[nama]
                            ,[alamat]
                            ,lokasi
                            ,id_kecamatan
                            ,id_kelurahan
                        FROM [dbo].[kendaraans]
                        WHERE id IN (
                            SELECT TOP (1000)
                                FLOOR(RAND(CHECKSUM(NEWID())) * (SELECT TOP 1 id FROM kendaraans ORDER BY id DESC)) + 1
                            FROM sys.all_objects
                        )
                        AND id_kendaraan NOT IN (SELECT id_kendaraan FROM winners)
                        AND id_kendaraan NOT IN (SELECT id FROM grand_prizes)
                    END
                ELSE
                    BEGIN
                        SELECT TOP (@length) [id]
                            ,[id_kendaraan]
                            ,[no_polisi]
                            ,roda
                            ,[nama]
                            ,[alamat]
                            ,lokasi
                            ,id_kecamatan
                            ,id_kelurahan
                        FROM [dbo].[kendaraans]
                        WHERE id_lokasi = @id_lokasi_samsat
                            AND id_kecamatan LIKE IIF(@id_kecamatan = '', '%', @id_kecamatan)
                            AND id_kendaraan NOT IN (SELECT id_kendaraan FROM winners)
                            AND id_kendaraan NOT IN (SELECT id FROM grand_prizes)
                            AND id IN (
                                SELECT
                                    FLOOR(RAND(CHECKSUM(NEWID())) * (SELECT TOP 1 id FROM kendaraans ORDER BY id DESC)) + 1
                                FROM sys.all_objects
                            )
                    END
            END";
    }

    public static function mySql(): string
    {
        return "
            CREATE PROCEDURE getWinner(
                IN p_id_lokasi_samsat INT,
                IN p_id_kecamatan VARCHAR(10),
                IN p_length INT
            )
            BEGIN
                -- MySQL tidak mendukung default argument, tangani NULL secara manual.
                IF p_length IS NULL THEN
                    SET p_length = 5;
                END IF;

                IF p_id_lokasi_samsat = 999 THEN
                    SET @sql = '
                        SELECT
                            id,
                            id_kendaraan,
                            no_polisi,
                            roda,
                            nama,
                            alamat,
                            lokasi,
                            id_kecamatan,
                            id_kelurahan
                        FROM kendaraans
                        WHERE id IN (
                            SELECT id FROM (
                                SELECT FLOOR(RAND() * (SELECT id FROM kendaraans ORDER BY id DESC LIMIT 1)) + 1 AS rand_id
                                FROM kendaraans
                                LIMIT 1000
                            ) AS temp_rand
                        )
                        AND id_kendaraan NOT IN (SELECT id_kendaraan FROM winners)
                        AND id_kendaraan NOT IN (SELECT id FROM grand_prizes)
                        LIMIT ?';

                    PREPARE stmt FROM @sql;
                    EXECUTE stmt USING p_length;
                    DEALLOCATE PREPARE stmt;
                ELSE
                    SET @sql = '
                        SELECT
                            id,
                            id_kendaraan,
                            no_polisi,
                            roda,
                            nama,
                            alamat,
                            lokasi,
                            id_kecamatan,
                            id_kelurahan
                        FROM kendaraans
                        WHERE id_lokasi = ?
                            AND id_kecamatan LIKE ?
                            AND id_kendaraan NOT IN (SELECT id_kendaraan FROM winners)
                            AND id_kendaraan NOT IN (SELECT id FROM grand_prizes)
                        ORDER BY RAND()
                        LIMIT ?';

                    SET @id_lokasi = p_id_lokasi_samsat;
                    SET @id_kecamatan = IF(p_id_kecamatan IS NULL OR p_id_kecamatan = '', '%', p_id_kecamatan);
                    SET @length = p_length;

                    PREPARE stmt FROM @sql;
                    EXECUTE stmt USING @id_lokasi, @id_kecamatan, @length;
                    DEALLOCATE PREPARE stmt;
                END IF;
            END";
    }
}
