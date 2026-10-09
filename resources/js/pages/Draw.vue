<template>
    <div id="page-container"
        class="sidebar-inverse enable-page-overlay side-scroll page-header-fixed side-trans-enabled">
        <x-header-comp/>
        <main id="main-container">
            <div class="content container-fluid">

            </div>
        </main>
        <Footer :appName="appName" :year="year"/>
    </div>
</template>

<script>
import Footer from '../componenets/Footer'


export default{
    components: {
        Footer
    },
}
</script>

<style scoped>
h1 {
    color: #42b983;
}
</style>
