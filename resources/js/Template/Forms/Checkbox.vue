<template>
    <div class="flex flex-col gap-4">
        <Card title="Checkbox Default">
            <div class="form-check">
                <input
                    name="checkbox_default"
                    type="checkbox"
                    class="form-check-input"
                    id="checkbox_default"
                />
                <label for="checkbox_default" class="form-check-label"
                    >Checkbox Default</label
                >
            </div>
            <div class="form-check">
                <input
                    name="checkbox_default"
                    type="checkbox"
                    class="form-check-input"
                    id="checkbox_default2"
                    checked
                />
                <label for="checkbox_default2" class="form-check-label"
                    >Checkbox Default Checked</label
                >
            </div>
        </Card>
        <Card title="Checkbox Button">
            <div class="flex flex-row gap-2">
                <div>
                    <input
                        type="checkbox"
                        class="btn-check peer"
                        name="button_checkbox"
                        id="button1"
                    />
                    <label
                        class="btn btn-outline-main dark:btn-outline-main-light"
                        for="button1"
                        >Button Checkbox</label
                    >
                </div>

                <div>
                    <input
                        type="checkbox"
                        class="btn-check peer"
                        name="button_checkbox"
                        id="button2"
                        checked
                    />
                    <label
                        class="btn btn-outline-main dark:btn-outline-main-light"
                        for="button2"
                        >Button Checkbox Checked</label
                    >
                </div>
            </div>
        </Card>
        <Card title="Checkbox Switch">
            <Switch name="switch_example" labeling="Example switch" />
        </Card>
        <Card title="Checkbox Switch Sizing">
            <div class="flex flex-col gap-2">
                <Switch
                    name="switch_small"
                    labeling="Example small"
                    size="sm"
                />
                <Switch name="switch_regular" labeling="Example regular" />
                <Switch
                    name="switch_large"
                    labeling="Example large"
                    size="lg"
                />
            </div>
        </Card>
    </div>
</template>
<script setup>
import Switch from "@/Components/Form/Switch.vue";
import Card from "@/Components/UI/Card.vue";
</script>
