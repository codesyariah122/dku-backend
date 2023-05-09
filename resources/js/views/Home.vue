<style>
.jumbotron-image {
    background-position: center center;
    background-repeat: no-repeat;
    background-size: cover;
    height: 500px;
}
</style>
<template>
    <default-layout>
        <div class="p-0 mb-4 bg-light rounded-3">
            <div class="container py-2">
                <div
                    class="jumbotron text-white jumbotron-image shadow"
                    :style="`
                        background-image: url(${asset_url}/assets/images/Banner2.png);
                    `"
                ></div>
            </div>
        </div>

        <div class="row align-items-md-stretch">
            <div class="col-md-6 bg-secondary">
                <div class="h-100 p-5 text-white bg-dark rounded-3">
                    <h2>{{ context.seo.title }}</h2>
                    <p>
                        {{ context.web.about }}
                    </p>
                    <button class="btn btn-outline-success" type="button">
                        Yuk Donasi
                    </button>
                </div>
            </div>
            <div class="col-md-6">
                <div class="h-100 p-5 bg-light border rounded-3">
                    <div class="d-flex justify-content-center">
                        <img
                            :src="`${asset_url}/assets/images/Banner3.jpeg`"
                            alt=""
                        />
                    </div>
                    <div class="container">
                        <div class="row justify-content-lg-center">
                            <div class="col col-lg-4 col-sm-12 col-md-12">
                                <button
                                    type="button"
                                    class="btn btn-outline-primary mt-4"
                                >
                                    DKU Web
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </default-layout>
</template>

<script>
import DefaultLayout from "../layouts/DefaultLayout";

export default {
    name: "Home",
    components: { DefaultLayout },
    mounted() {
        console.log(this.$context);
    },
    data() {
        return {
            context: window?.context,
            asset_url: window.location.origin,
        };
    },
};
</script>
