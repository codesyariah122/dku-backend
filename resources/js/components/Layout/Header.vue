<template>
    <header class="pb-3 mb-4 border-bottom">
        <a
            href="/"
            class="d-flex align-items-center text-dark text-decoration-none"
        >
        <img :src="`${asset_url}/assets/img/logo-dku.png`" alt="" width="150" />
        </a>
    </header>
</template>

<script>
export default {
    data() {
        return {
            asset_url: window.location.origin,
        };
    },
};
</script>
