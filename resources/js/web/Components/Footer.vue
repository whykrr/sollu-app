<template>
    <footer
        class="bg-web-main min-h-[100px] text-white px-2 md:px-8 xl:px-16 py-6"
    >
        <div
            class="grid grid-rows-1 md:grid-rows-[auto_auto_auto_auto] xl:grid-rows-[auto_auto] md:grid-cols-2 xl:grid-cols-4 md:grid-flow-col gap-4 mb-2"
        >
            <div class="font-bold text-2xl mt-auto">SMANDA Tegineneng</div>
            <div class="">
                <div class="text-sm italic">"{{ others["slogan"] }}"</div>
                <div class="flex flex-row gap-2 mt-2 text-lg">
                    <a :href="socialMedia.facebook">
                        <fa icon="fa-brands fa-facebook" />
                    </a>
                    <a :href="socialMedia.instagram">
                        <fa icon="fa-brands fa-instagram" />
                    </a>
                    <a :href="socialMedia.tiktok">
                        <fa icon="fa-brands fa-tiktok" />
                    </a>
                    <a :href="socialMedia.youtube">
                        <fa icon="fa-brands fa-youtube" />
                    </a>
                </div>
            </div>
            <div class="font-semibold font-merriweather mt-auto">
                Kontak Sekolah
            </div>
            <div class="flex flex-col gap-2">
                <div>Email : info@sman2-tegineneng.sch.id</div>
                <div>Telp : +{{ socialMedia.whatsapp }}</div>
                <p>
                    Jalan Ngudi Ilmu No. 25 Desa Trimulyo Kecamatan Tegineneng
                    Kab. Pesawaran Prov. Lampung
                </p>
            </div>
            <div class="font-semibold font-merriweather mt-auto">Tautan</div>
            <div class="flex flex-col gap-2">
                <a :href="others['tautan-spmb']" target="_blank">SPMB</a>
                <div v-for="link in links">
                    <a :href="link['tautan']">{{ link["title"] }}</a>
                </div>
            </div>
            <div class="font-semibold font-merriweather mt-auto">
                Google Map
            </div>
            <div class="w-fit">
                <iframe
                    src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3973.4792273213257!2d105.12017027504173!3d-5.187084994790438!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x2e40b5de33b40031%3A0xe1020f92ab6af5ef!2sSMAN%202%20Tegineneng!5e0!3m2!1sid!2sid!4v1738402403821!5m2!1sid!2sid"
                    class="w-full"
                    height="150"
                    style="border: 0"
                    allowfullscreen=""
                    loading="lazy"
                    referrerpolicy="no-referrer-when-downgrade"
                ></iframe>
            </div>
        </div>
        <hr />
        <div class="flex flex-row justify-between pt-3 text-sm">
            <div>Hak Cipta &copy; {{ year }}</div>
            <div>Sollu Teknologi Indonesia</div>
        </div>
    </footer>
</template>
<script setup>
import { usePage } from "@inertiajs/vue3";

const page = usePage();
const others = page.props.others;
const socialMedia = page.props.socialMedia;
const links = page.props.links;
const year = new Date().getFullYear();
</script>
