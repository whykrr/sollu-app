<template>
    <div class="flex flex-col gap-4">
        <Card title="Radio Default">
            <div class="form-check">
                <input
                    name="radio_default"
                    type="radio"
                    class="form-check-input"
                    id="radio_default"
                />
                <label for="radio_default" class="form-check-label"
                    >Radio Default</label
                >
            </div>
            <div class="form-check">
                <input
                    name="radio_default"
                    type="radio"
                    class="form-check-input"
                    id="radio_default2"
                    checked
                />
                <label for="radio_default2" class="form-check-label"
                    >Radio Default Checked</label
                >
            </div>
        </Card>
        <Card title="Buttons Radio">
            <div class="flex flex-row gap-2">
                <div>
                    <input
                        type="radio"
                        class="btn-check peer"
                        name="button_radio"
                        id="button1"
                    />
                    <label
                        class="btn btn-outline-main dark:btn-outline-main-light"
                        for="button1"
                        >Button Radio</label
                    >
                </div>

                <div>
                    <input
                        type="radio"
                        class="btn-check peer"
                        name="button_radio"
                        id="button2"
                        checked
                    />
                    <label
                        class="btn btn-outline-main dark:btn-outline-main-light"
                        for="button2"
                        >Button Radio Checked</label
                    >
                </div>
            </div>
        </Card>
    </div>
</template>
<script setup>
import Card from "@/Components/UI/Card.vue";
</script>
