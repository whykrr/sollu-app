<template>
    <Container>
        <div class="flex flex-col gap-4">
            <Card title="Navigation">
                <div class="navigation">
                    <ul>
                        <li class="active">
                            <Link class="btn" href="#">Active</Link>
                        </li>
                        <li>
                            <Link class="btn" href="#">Link</Link>
                        </li>
                        <li>
                            <Link class="btn" href="#">Link</Link>
                        </li>
                    </ul>
                </div>
            </Card>
            <Card title="Tab Icon">
                <Tab :pages="pages" />
            </Card>
            <Card title="Vertical Tab">
                <Tab vertical="true" :pages="pages" />
            </Card>
        </div>
    </Container>
</template>
<script setup>
import Card from "@/Components/Dashboard/UI/Card/Card.vue";
import Container from "@/Components/Dashboard/UI/Container.vue";
import Tab from "@/Components/Dashboard/UI/Tab.vue";
import { faBell, faHome, faUser } from "@fortawesome/free-solid-svg-icons";
import { Link } from "@inertiajs/vue3";
import { ref } from "vue";
import Home from "./NavigationPages/Home.vue";
import Profile from "./NavigationPages/Profile.vue";
import Notification from "./NavigationPages/Notif.vue";

const activeTab = ref("home");
const activeTabVertical = ref("home");
const activeTabIcon = ref("home");

const pages = [
    { name: "home", label: "Home", icon: faHome, page: Home },
    { name: "profile", label: "Profile", icon: faUser, page: Profile },
    {
        name: "notification",
        label: "Notification",
        icon: faBell,
        page: Notification,
    },
];

const changeTab = (tab) => {
    activeTab.value = tab;
};
const changeTabVertical = (tab) => {
    activeTabVertical.value = tab;
};
const changeTabIcon = (tab) => {
    activeTabIcon.value = tab;
};
</script>
