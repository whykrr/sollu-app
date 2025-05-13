<template>
    <div class="flex flex-col gap-4">
        <Card title="Alert">
            <div class="flex flex-col gap-4">
                <div class="alert">Basic Alert</div>
                <div class="alert alert-success">Alert Success</div>
                <div class="alert alert-warning">Alert Warning</div>
                <div class="alert alert-danger">Alert Danger</div>
            </div>
        </Card>
        <Card title="Alert with Content">
            <div class="flex flex-col gap-4">
                <div class="alert alert-success">
                    <strong>Well Done!</strong>
                    <div class="text-sm">
                        Lorem ipsum dolor sit, amet consectetur adipisicing
                        elit. Facere possimus numquam repellendus minus
                        doloribus reprehenderit alias veniam. Repudiandae sed
                        mollitia aliquam aperiam, est molestias eveniet. Vero
                        placeat esse enim soluta.
                    </div>
                </div>
            </div>
        </Card>
    </div>
</template>
<script setup>
import Card from "@/Components/UI/Card.vue";
</script>
