<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $this->down();

        match (DB::connection()->getDriverName()) {
            'mysql', 'mariadb' => $this->getWinnerMySql(),
            'sqlsrv'           => $this->getWinnerSqlsrv(),
            default => $this->getWinnerMySql()
        };
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (DB::connection()->getDriverName() == 'sqlsrv') {
            DB::unprepared("IF (SELECT 1 FROM sys.procedures WHERE name LIKE '%getWinner%') > 0
                BEGIN
                    DROP PROCEDURE dbo.getWinner
                END");
        } else {
            DB::unprepared("DROP PROCEDURE IF EXISTS getWinner");
        }
    }

    protected function getWinnerMySql(): Void
    {
        DB::unprepared("
        CREATE PROCEDURE getWinner(
            IN p_id_lokasi_samsat INT,
            IN p_length INT
        )
        BEGIN
            -- Mengatasi default value parameter karena MySQL tidak mendukung default argument secara native di SP
            IF p_length IS NULL THEN
                SET p_length = 5;
            END IF;

            IF p_id_lokasi_samsat = 999 THEN
                -- Menggunakan PREPARED STATEMENT karena LIMIT tidak bisa menerima variabel langsung di beberapa versi MySQL
                SET @sql = '
                    SELECT 
                        id,
                        id_kendaraan,
                        no_polisi,
                        roda,
                        nama,
                        alamat,
                        lokasi
                    FROM kendaraans
                    WHERE id IN (
                        SELECT id FROM (
                            SELECT FLOOR(RAND() * (SELECT id FROM kendaraans ORDER BY id DESC LIMIT 1)) + 1 AS rand_id
                            -- Menggunakan urutan angka acak berbasis RAND() untuk menggantikan fungsi NEWID() SQL Server
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
                        lokasi
                    FROM kendaraans
                    WHERE id_lokasi = ?
                        AND id_kendaraan NOT IN (SELECT id_kendaraan FROM winners)
                        -- RAND() di MySQL menggantikan NEWID() untuk pengacakan baris
                        AND id_kendaraan NOT IN (SELECT id FROM grand_prizes)
                    ORDER BY RAND()
                    LIMIT ?';
                    
                PREPARE stmt FROM @sql;
                EXECUTE stmt USING p_id_lokasi_samsat, p_length;
                DEALLOCATE PREPARE stmt;
            END IF;
        END");
    }

    protected function getWinnerSqlsrv(): Void
    {
        DB::unprepared("
            CREATE PROCEDURE [dbo].[getWinner] 
                -- Add the parameters for the stored procedure here
                    @id_lokasi_samsat INT
                    ,@length INT =5
            AS
            BEGIN
                -- SET NOCOUNT ON added to prevent extra result sets from
                -- interfering with SELECT statements.
                SET NOCOUNT ON;

                -- Insert statements for procedure here
            IF @id_lokasi_samsat=999
                BEGIN
                    SELECT TOP (@length) 
                        [id]
                        ,[id_kendaraan]
                        ,[no_polisi]
                        ,roda
                        ,[nama]
                        ,[alamat]
                        ,lokasi
                    FROM [dbo].[kendaraans]
                    WHERE id in (
                        SELECT TOP (1000)
                            FLOOR(RAND(CHECKSUM(NEWID())) * (SELECT top 1 id FROM kendaraans ORDER BY id desc)) + 1
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
                    FROM [dbo].[kendaraans]
                        WHERE id_lokasi=@id_lokasi_samsat
                        AND id_kendaraan NOT IN (SELECT id_kendaraan FROM winners)
                        AND id_kendaraan NOT IN (SELECT id FROM grand_prizes)
                        AND id in (
                        SELECT 
                            FLOOR(RAND(CHECKSUM(NEWID())) * (SELECT top 1 id FROM kendaraans ORDER BY id desc)) + 1
                        FROM sys.all_objects
                        )
                END
            END");
    }
};
