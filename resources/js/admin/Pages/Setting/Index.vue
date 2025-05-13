<template>
    <div class="flex flex-col gap-4">
        <Card title="Website" v-access="['superadmin']">
            <form @submit.prevent="submitWebsite" class="mb-0">
                <div class="grid grid-cols-4 items-center gap-y-2">
                    <div>
                        <label for="website_name">{{ $t("field.name") }}</label>
                    </div>
                    <div class="col-span-3">
                        <input
                            type="text"
                            id="website_name"
                            class="form w-full"
                            v-model="formWebsite.name"
                            :class="{
                                'is-invalid': formWebsite.errors.name,
                            }"
                        />
                        <span class="form-feedback">{{
                            formWebsite.errors.name
                        }}</span>
                    </div>

                    <div>
                        <label for="website_address">{{
                            $t("field.address")
                        }}</label>
                    </div>
                    <div class="col-span-3">
                        <textarea
                            id="website_address"
                            class="form w-full"
                            v-model="formWebsite.address"
                        ></textarea>
                    </div>

                    <div>
                        <label for="website_logo">{{ $t("field.logo") }}</label>
                    </div>
                    <div class="col-span-3">
                        <img
                            v-if="website.value.logo"
                            :src="website.value.logo"
                            class="h-32 mb-2"
                            alt="Website Logo"
                        />
                        <input
                            id="website_logo"
                            type="file"
                            class="form w-full"
                            accept="image/*"
                            @input="addFilesLogo"
                            :class="{
                                'is-invalid': formWebsite.errors.logo,
                            }"
                        />
                        <span class="form-feedback">{{
                            formWebsite.errors.logo
                        }}</span>
                        <span class="text-xs block">
                            {{ $t("field.maxFileSize") }} 3MB
                        </span>
                        <span class="text-xs">
                            {{ $t("message.imageReplace") }}
                        </span>
                    </div>

                    <div><label for="website_icon">Ikon</label></div>
                    <div class="col-span-3">
                        <img
                            v-if="website.value.icon"
                            :src="website.value.icon"
                            class="h-32 mb-2"
                            alt="Website Icon"
                        />
                        <input
                            id="website_icon"
                            type="file"
                            class="form w-full"
                            accept="image/*"
                            @input="addFilesIcon"
                            :class="{
                                'is-invalid': formWebsite.errors.icon,
                            }"
                        />
                        <span class="form-feedback">{{
                            formWebsite.errors.icon
                        }}</span>
                        <span class="text-xs block">
                            {{ $t("field.maxFileSize") }} 1MB
                        </span>
                        <span class="text-xs block">
                            {{
                                $t("message.ratioFormat", {
                                    value: "1:1",
                                })
                            }}
                        </span>
                        <span class="text-xs">
                            {{ $t("message.imageReplace") }}
                        </span>
                    </div>

                    <div><label>Multi Bahasa</label></div>
                    <div class="col-span-3">
                        <Switch v-model="formWebsite.multiple_language" />
                    </div>

                    <div class="col-span-4 flex justify-end">
                        <button type="submit" class="btn btn-main">
                            {{ $t("action.submit") }}
                        </button>
                    </div>
                </div>
            </form>
        </Card>
        <Card :title="$t('field.socialMedia')">
            <form @submit.prevent="submitSocialMedia" class="mb-0">
                <div class="grid grid-cols-4 items-center gap-y-2">
                    <div>
                        <label for="ss_facebook"
                            >{{ $t("field.link") }} Facebook</label
                        >
                    </div>
                    <div class="col-span-3">
                        <input
                            type="text"
                            id="ss_facebook"
                            class="form w-full"
                            v-model="formSocialMedia.facebook"
                            :class="{
                                'is-invalid': formSocialMedia.errors.facebook,
                            }"
                        />
                        <span class="form-feedback">{{
                            formSocialMedia.errors.facebook
                        }}</span>
                    </div>

                    <div>
                        <label for="ss_instagram"
                            >{{ $t("field.link") }} Instagram</label
                        >
                    </div>
                    <div class="col-span-3">
                        <input
                            type="text"
                            id="ss_instagram"
                            class="form w-full"
                            v-model="formSocialMedia.instagram"
                            :class="{
                                'is-invalid': formSocialMedia.errors.instagram,
                            }"
                        />
                        <span class="form-feedback">{{
                            formSocialMedia.errors.instagram
                        }}</span>
                    </div>

                    <div>
                        <label for="ss_x">{{ $t("field.link") }} X</label>
                    </div>
                    <div class="col-span-3">
                        <input
                            type="text"
                            id="ss_x"
                            class="form w-full"
                            v-model="formSocialMedia.x"
                            :class="{
                                'is-invalid': formSocialMedia.errors.x,
                            }"
                        />
                        <span class="form-feedback">{{
                            formSocialMedia.errors.x
                        }}</span>
                    </div>

                    <div>
                        <label for="ss_youtube"
                            >{{ $t("field.link") }} Channel Youtube</label
                        >
                    </div>
                    <div class="col-span-3">
                        <input
                            type="text"
                            id="ss_youtube"
                            class="form w-full"
                            v-model="formSocialMedia.youtube"
                            :class="{
                                'is-invalid': formSocialMedia.errors.youtube,
                            }"
                        />
                        <span class="form-feedback">{{
                            formSocialMedia.errors.youtube
                        }}</span>
                    </div>

                    <div>
                        <label for="ss_tiktok"
                            >{{ $t("field.link") }} Tiktok</label
                        >
                    </div>
                    <div class="col-span-3">
                        <input
                            type="text"
                            id="ss_tiktok"
                            class="form w-full"
                            v-model="formSocialMedia.tiktok"
                            :class="{
                                'is-invalid': formSocialMedia.errors.tiktok,
                            }"
                        />
                        <span class="form-feedback">{{
                            formSocialMedia.errors.tiktok
                        }}</span>
                    </div>

                    <div>
                        <label for="ss_whatsapp">{{
                            $t("field.whatsappNumber")
                        }}</label>
                    </div>
                    <div class="col-span-3">
                        <input
                            type="text"
                            id="ss_whatsapp"
                            class="form w-full"
                            v-model="formSocialMedia.whatsapp"
                            :class="{
                                'is-invalid': formSocialMedia.errors.whatsapp,
                            }"
                        />
                        <span class="form-feedback">{{
                            formSocialMedia.errors.whatsapp
                        }}</span>
                    </div>

                    <div class="col-span-4 flex justify-end">
                        <button type="submit" class="btn btn-main">
                            {{ $t("action.submit") }}
                        </button>
                    </div>
                </div>
            </form>
        </Card>
        <Card title="Sistem">
            <form @submit.prevent="submitSystem" class="mb-0">
                <div class="grid grid-cols-4 items-center gap-y-2">
                    <div>
                        <label for="system_language">{{
                            $t("field.language")
                        }}</label>
                    </div>
                    <div class="col-span-3">
                        <select
                            id="system_language"
                            class="form w-full"
                            v-model="formSystem.language"
                        >
                            <option value="id">Indonesia</option>
                            <option value="en">English</option>
                        </select>
                    </div>

                    <div class="col-span-4 flex justify-end">
                        <button type="submit" class="btn btn-main">
                            {{ $t("action.submit") }}
                        </button>
                    </div>
                </div>
            </form>
        </Card>
    </div>
</template>

<script setup>
import Switch from "@admin/Components/Form/Switch.vue";
import Card from "@admin/Components/UI/Card.vue";
import i18n from "@admin/i18n";
import { useForm } from "@inertiajs/vue3";

const props = defineProps({
    website: Object,
    socialMedia: Object,
    system: Object,
});

const formWebsite = useForm({
    key: props.website.key,
    name: props.website.value.name ?? "",
    address: props.website.value.address ?? "",
    logo: null,
    icon: null,
    multiple_language: props.website.value.multiple_language ?? false,
});

const formSocialMedia = useForm({
    key: props.socialMedia.key,
    facebook: props.socialMedia.value.facebook ?? "",
    instagram: props.socialMedia.value.instagram ?? "",
    x: props.socialMedia.value.facebook.x ?? "",
    youtube: props.socialMedia.value.youtube ?? "",
    tiktok: props.socialMedia.value.tiktok ?? "",
    whatsapp: props.socialMedia.value.whatsapp ?? "",
});

const formSystem = useForm({
    key: props.system.key,
    language: props.system.value.language ?? "",
});

const addFilesLogo = (event) => {
    formWebsite.logo = event.target.files[0];
};

const addFilesIcon = (event) => {
    formWebsite.icon = event.target.files[0];
};

const submitWebsite = () =>
    formWebsite.post(
        route("admin.settings.update", {
            setting: props.website.key,
        })
    );

const submitSocialMedia = () =>
    formSocialMedia.post(
        route("admin.settings.update", {
            setting: props.socialMedia.key,
        })
    );

const submitSystem = () => {
    i18n.global.locale.value = formSystem.language;

    formSystem.post(
        route("admin.settings.update", {
            setting: props.system.key,
        })
    );
};
</script>
