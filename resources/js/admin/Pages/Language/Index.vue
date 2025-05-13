<template>
    <div class="flex flex-col">
        <CardTransparent :title="$t('page.language')">
            <div class="grid grid-cols-1 sm:grid-cols-1 lg:grid-cols-3 gap-2">
                <Card
                    v-for="lang in languages"
                    class="card-outline"
                    :title="lang.name"
                >
                    <template #buttons>
                        <span
                            v-if="lang.is_default"
                            class="badge badge-success text-sm"
                            >Default</span
                        >
                    </template>
                    <div class="-mb-2">
                        <Link
                            :href="
                                route('admin.languages.default', {
                                    language: lang.id,
                                })
                            "
                            class="btn btn-sm btn-outline-main mr-2 mb-2"
                            method="PUT"
                            as="button"
                        >
                            {{ $t("action.setDefault") }}
                        </Link>
                        <Link
                            :href="
                                route('admin.languages.show', {
                                    language: lang.id,
                                })
                            "
                            class="btn btn-sm btn-outline-blue-500 mr-2 mb-2"
                        >
                            {{ $t("action.detail") }}
                        </Link>
                        <button
                            type="button"
                            class="btn btn-sm btn-outline-danger mr-2 mb-2"
                            @click="
                                modalStore.openModalDelete(
                                    route('admin.languages.destroy', {
                                        language: lang.id,
                                    })
                                )
                            "
                        >
                            {{ $t("action.delete") }}
                        </button>
                    </div>
                </Card>
                <Link
                    :href="route('admin.languages.create')"
                    class="card card-outline text-2xl text-gray-400 flex items-center justify-center"
                >
                    <div>
                        <fa icon="fa-plus" />
                        {{ $t("action.create") }}
                    </div>
                </Link>
            </div>
        </CardTransparent>
    </div>
</template>

<script setup>
import Card from "@admin/Components/UI/Card.vue";
import { Link } from "@inertiajs/vue3";
import { useModalStore } from "@admin/store/modal";
import CardTransparent from "@admin/Components/UI/CardTransparent.vue";

defineProps({
    languages: Array,
});

const modalStore = useModalStore();
</script>
