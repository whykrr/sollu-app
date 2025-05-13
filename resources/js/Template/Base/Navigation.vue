<template>
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
        <Card title="Tab">
            <Tab>
                <template #navigation>
                    <TabNav
                        :class="{ active: activeTab === 'home' }"
                        label="Home"
                        @click="changeTab('home')"
                    />
                    <TabNav
                        :class="{ active: activeTab === 'profile' }"
                        label="Profile"
                        @click="changeTab('profile')"
                    />
                    <TabNav
                        :class="{ active: activeTab === 'notification' }"
                        label="Notification"
                        @click="changeTab('notification')"
                    />
                </template>
                <TabContent :class="{ active: activeTab === 'home' }">
                    Tab Home
                </TabContent>
                <TabContent :class="{ active: activeTab === 'profile' }">
                    Tab Profile
                </TabContent>
                <TabContent :class="{ active: activeTab === 'notification' }">
                    Tab Notification
                </TabContent>
            </Tab>
        </Card>
        <Card title="Tab Icon">
            <Tab>
                <template #navigation>
                    <TabNav
                        :class="{ active: activeTabIcon === 'home' }"
                        label="Home"
                        icon="fa-home"
                        @click="changeTabIcon('home')"
                    />
                    <TabNav
                        :class="{ active: activeTabIcon === 'profile' }"
                        label="Profile"
                        icon="fa-user"
                        @click="changeTabIcon('profile')"
                    />
                    <TabNav
                        :class="{ active: activeTabIcon === 'notification' }"
                        label="Notification"
                        icon="fa-bell"
                        @click="changeTabIcon('notification')"
                    />
                </template>
                <TabContent :class="{ active: activeTabIcon === 'home' }">
                    Tab Home
                </TabContent>
                <TabContent :class="{ active: activeTabIcon === 'profile' }">
                    Tab Profile
                </TabContent>
                <TabContent
                    :class="{ active: activeTabIcon === 'notification' }"
                >
                    Tab Notification
                </TabContent>
            </Tab>
        </Card>
        <Card title="Vertical Tab">
            <TabVertical>
                <template #navigation>
                    <TabNav
                        :class="{ active: activeTabVertical === 'home' }"
                        label="Home"
                        @click="changeTabVertical('home')"
                    />
                    <TabNav
                        :class="{ active: activeTabVertical === 'profile' }"
                        label="Profile"
                        @click="changeTabVertical('profile')"
                    />
                    <TabNav
                        :class="{
                            active: activeTabVertical === 'notification',
                        }"
                        label="Notification"
                        @click="changeTabVertical('notification')"
                    />
                </template>
                <TabContent :class="{ active: activeTabVertical === 'home' }">
                    Tab Home
                    <p>
                        Lorem ipsum, dolor sit amet consectetur adipisicing
                        elit. Repellendus dolore ea, cumque et inventore
                        consectetur minima ut possimus quasi a qui quisquam
                        harum perferendis. Ut dicta ducimus tenetur saepe
                        deleniti.
                    </p>
                </TabContent>
                <TabContent
                    :class="{ active: activeTabVertical === 'profile' }"
                >
                    Tab Profile
                </TabContent>
                <TabContent
                    :class="{ active: activeTabVertical === 'notification' }"
                >
                    Tab Notification
                </TabContent>
            </TabVertical>
        </Card>
    </div>
</template>

<script setup>
import Tab from "@/Components/UI/Tab.vue";
import TabContent from "@/Components/UI/TabContent.vue";
import TabNav from "@/Components/UI/TabNav.vue";
import TabVertical from "@/Components/UI/TabVertical.vue";
import Card from "@/Components/UI/Card.vue";
import { Link } from "@inertiajs/vue3";
import { ref } from "vue";

const activeTab = ref("home");
const activeTabVertical = ref("home");
const activeTabIcon = ref("home");

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
