<template>
    <footer id="page-footer">
        <div class="content py-3">
            <div class="row fs-sm">
                <div class="col-sm-6 order-sm-2 py-1 text-center text-sm-end">
                    Crafted with <i class="fa fa-heart text-danger"></i> by
                    <span class="fw-semibold">PSI - Lahtabang</span>
                </div>
                <div class="col-sm-6 order-sm-1 py-1 text-center text-sm-start">
                    <span class="fw-semibold">{{ appName }}</span> ©
                    <span
                        data-toggle="year-copy"
                        class="js-year-copy-enabled"
                        >{{ year }}</span
                    >
                </div>
            </div>
        </div>
    </footer>
</template>
<script setup>
import { inject } from 'vue';
const appName   = window.appConfig.appName;
const year      = inject('year');
</script>
