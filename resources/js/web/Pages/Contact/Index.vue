<template>
    <div class="py-10">
        <div class="px-2 md:px-8 xl:px-16 flex flex-col gap-4">
            <div
                class="text-2xl font-bold text-web-main text-center font-merriweather appear"
            >
                Kontak Sekolah
            </div>
            <div class="flex flex-col-reverse md:grid grid-cols-10 gap-8">
                <div class="col-span-6">
                    <div class="font-merriweather font-semibold text-2xl mb-2">
                        Kirim Pesan
                    </div>
                    <div
                        v-if="onSuccess"
                        class="px-3 py-2 rounded bg-green-500/50 border border-green-500 mb-2"
                    >
                        Pesan berhasil Terkirim!
                    </div>
                    <form
                        @submit.prevent="sendMessage"
                        class="flex flex-col gap-1 mb-0"
                    >
                        <div>
                            <label for="name">Nama</label>
                            <input
                                id="name"
                                type="text"
                                class="form"
                                :class="{
                                    'is-invalid': form.errors.name,
                                }"
                                v-model="form.name"
                            />
                            <span class="form-feedback">{{
                                form.errors.name
                            }}</span>
                        </div>
                        <div>
                            <label for="email">Email</label>
                            <input
                                id="email"
                                type="email"
                                class="form"
                                :class="{
                                    'is-invalid': form.errors.email,
                                }"
                                v-model="form.email"
                            />
                            <span class="form-feedback">{{
                                form.errors.email
                            }}</span>
                        </div>
                        <div>
                            <label for="subject">Subjek</label>
                            <input
                                id="subject"
                                type="text"
                                class="form"
                                :class="{
                                    'is-invalid': form.errors.subject,
                                }"
                                v-model="form.subject"
                            />
                            <span class="form-feedback">{{
                                form.errors.subject
                            }}</span>
                        </div>

                        <div>
                            <label for="message">Pesan</label>
                            <textarea
                                id="message"
                                class="form"
                                :class="{
                                    'is-invalid': form.errors.message,
                                }"
                                v-model="form.message"
                            />
                            <span class="form-feedback">{{
                                form.errors.message
                            }}</span>
                        </div>

                        <div class="mt-2">
                            <button
                                class="px-3 py-2 bg-web-main rounded text-white hover:brightness-110"
                                type="submit"
                            >
                                <fa icon="fa-paper-plane" />
                                Kirim
                            </button>
                        </div>
                    </form>
                </div>
                <div class="col-span-4">
                    <div class="font-merriweather font-semibold text-2xl mb-2">
                        Detail Informasi
                    </div>
                    <div class="flex flex-col gap-2">
                        <div>Email : info@sman2-tegineneng.sch.id</div>
                        <div>Telp : +{{ socialMedia.whatsapp }}</div>
                        <div class="font-semibold mb-0 mt-4">Alamat :</div>
                        <p>
                            Jalan Ngudi Ilmu No. 25 Desa Trimulyo Kecamatan
                            Tegineneng Kab. Pesawaran Prov. Lampung
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
<script setup>
import { useForm, usePage } from "@inertiajs/vue3";
import { computed, onMounted, ref } from "vue";

const page = usePage();
const socialMedia = page.props.socialMedia;

const onSuccess = ref(page.props.flash.success);

const form = useForm({
    name: null,
    email: null,
    subject: null,
    message: null,
});

onMounted(() => {
    setTimeout(() => {
        onSuccess.value = null;
    }, 3000);
});

const sendMessage = () =>
    form.post(route("contact.store"), {
        preserveState: false,
    });
</script>
