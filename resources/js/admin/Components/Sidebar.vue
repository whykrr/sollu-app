<template>
    <div class="hidden sm:flex w-64 md:w-72 h-screen z-30">
        <div
            class="bg-neutral-lighter dark:bg-neutral-darker text-neutral-600 dark:text-neutral-400 w-full flex flex-col justify-between shadow"
        >
            <div class="p-4 pb-6">
                <img
                    src="storage/img/logo-fit-color.png"
                    class="w-28"
                    alt="Sollu"
                />
            </div>
            <div class="flex-1 overflow-y-auto floating-scrollbar">
                <nav class="flex flex-col gap-0.5 px-2 py-0">
                    <SidebarItem
                        :to="route('admin.dashboard')"
                        icon="fa-home"
                        :name="$t('sidebar.dashboard')"
                        :is-active="menuActive === 'admin.dashboard'"
                    />

                    <SidebarItem
                        v-access="['superadmin', 'admin']"
                        :to="route('admin.users.index')"
                        icon="fa-users"
                        :name="$t('sidebar.users')"
                        :is-active="menuActive.includes('admin.users.')"
                    />

                    <SidebarItem
                        v-access="['superadmin']"
                        :to="route('admin.languages.index')"
                        icon="fa-language"
                        :name="$t('sidebar.languages')"
                        :is-active="menuActive.includes('admin.languages.')"
                    />

                    <SidebarItem
                        v-access="['superadmin']"
                        :to="route('admin.content-types.index')"
                        icon="fa-list"
                        :name="$t('sidebar.contentTypes')"
                        :is-active="menuActive.includes('admin.content-types.')"
                    />

                    <SidebarItemExpand
                        to="#"
                        icon="fa-icons"
                        :name="$t('sidebar.contents')"
                        :is-active="menuActive.includes('admin.contents.')"
                    >
                        <SidebarItem
                            v-for="sb in contentSidebar"
                            :to="
                                sb.is_listed
                                    ? route('admin.contents.listed', {
                                          content_type: sb.id,
                                      })
                                    : route('admin.contents.index', {
                                          content_type: sb.id,
                                      })
                            "
                            :name="sb.name"
                            :is-active="
                                menuActive === 'admin.contents.' + sb.id
                            "
                        />
                    </SidebarItemExpand>

                    <SidebarItem
                        v-access="['superadmin', 'admin', 'editor']"
                        :to="route('admin.message.index')"
                        icon="fa-envelope"
                        :name="$t('sidebar.messages')"
                        :is-active="menuActive.includes('admin.message.')"
                    />

                    <SidebarItem
                        v-access="['superadmin', 'admin']"
                        :to="route('admin.settings.index')"
                        icon="fa-cogs"
                        :name="$t('sidebar.settings')"
                        :is-active="menuActive.includes('admin.settings.')"
                    />
                </nav>
            </div>
            <div class="p-2 relative">
                <button
                    type="button"
                    class="bg-gradient-to-r from-main to-secondary rounded-md p-2 cursor-pointer w-full"
                    @click="toggleAccountDropdown"
                >
                    <div class="flex flex-row gap-2 items-center text-white">
                        <div>
                            <img
                                src="storage/img/profile-pic.png"
                                alt="Profile"
                                class="rounded-full w-[35px] h-[35px]"
                            />
                        </div>
                        <div class="grow text-left">
                            {{ auth.name }}
                        </div>
                        <fa
                            icon="fa-chevron-up"
                            class="transition-transform duration-200"
                            :class="{ 'rotate-180': accountDropdown }"
                        />
                    </div>
                </button>
                <div
                    v-if="accountDropdown"
                    class="fixed inset-0"
                    @click="toggleAccountDropdown"
                />
                <div
                    class="dropdown-account"
                    :class="{
                        show: accountDropdown,
                    }"
                >
                    <div
                        class="flex flex-row gap-2 items-center border-b border-gray-200 mb-0.5"
                    >
                        <div>
                            <img
                                src="storage/img/profile-pic.png"
                                alt="Profile"
                                class="rounded-full w-[50px] h-[50px]"
                            />
                        </div>
                        <div class="grow">
                            <div class="font-semibold">
                                {{ auth.name }}
                            </div>
                            <div class="text-sm">
                                {{ roleLabel[auth.role] }}
                            </div>
                        </div>
                    </div>
                    <div class="flex flex-col gap-0.5 text-sm">
                        <Link
                            :href="route('admin.change_password.index')"
                            class="sidebar-item rounded-lg px-2 py-1"
                        >
                            <fa icon="fa-key"></fa>
                            {{ $t("link.changePassword") }}
                        </Link>
                        <a
                            :href="route('admin.logout')"
                            class="sidebar-item rounded-lg px-2 py-1"
                        >
                            <fa icon="fa-right-from-bracket"></fa>
                            {{ $t("link.logout") }}
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import SidebarItem from "@admin/Components/UI/SidebarItem.vue";
import SidebarItemExpand from "@admin/Components/UI/SidebarItemExpand.vue";
import { Link, usePage } from "@inertiajs/vue3";
import { computed, ref, watch, watchEffect } from "vue";

const page = usePage();
const auth = computed(() => page.props.auth);
const menuActive = computed(() => page.props.menuActive);
const contentSidebar = computed(() => page.props.contentSidebar);

const accountDropdown = ref(false);

const toggleAccountDropdown = () =>
    (accountDropdown.value = !accountDropdown.value);

watch(
    () => page.url,
    () => {
        accountDropdown.value = false;
    }
);

const roleLabel = {
    superadmin: "Super Admin",
    admin: "Admin",
    creator: "Creator",
    viewer: "Viewer",
};
</script>
