<template>
    <Container>
        <div class="grid grid-cols-2 gap-4">
            <div class="flex flex-col gap-4">
                <Card title="Basic">
                    <div class="grid grid-flow-row">
                        <InputBasic
                            label="Example Input"
                            placeholder="Example..."
                            v-model="form.exampleInput"
                        />
                        <TextareaBasic
                            label="Example Textarea"
                            placeholder="Example ..."
                            v-model="form.exampleTextarea"
                        />
                        <SelectBasic
                            label="Example Select"
                            :options="[
                                { value: 1, label: 'Satu' },
                                { value: 2, label: 'Dua' },
                                { value: 3, label: 'Tiga' },
                            ]"
                            v-model="form.exampleSelect"
                        />
                    </div>
                </Card>
                <Card title="Form Size">
                    <div class="space-y-2">
                        <InputBasic
                            size="sm"
                            label="Example Small"
                            placeholder="Example..."
                        />

                        <InputBasic
                            label="Example Regular"
                            placeholder="Example..."
                        />

                        <InputBasic
                            size="lg"
                            label="Example Large"
                            placeholder="Example..."
                        />
                    </div>
                </Card>
                <Card title="Form Floating">
                    <div class="flex flex-col gap-2">
                        <div class="form-floating sm">
                            <input
                                type="text"
                                id="name"
                                placeholder="asd"
                                required
                                class="form sm"
                            />
                            <label for="name">Name</label>
                        </div>
                        <div>
                            <div class="form-floating is-invalid">
                                <input
                                    type="text"
                                    id="name"
                                    placeholder="asd"
                                    required
                                    class="form"
                                />
                                <label for="name">Name</label>
                            </div>
                            <div class="form-feedback">Invalid !</div>
                        </div>
                        <div>
                            <div class="form-floating is-valid">
                                <input
                                    type="text"
                                    id="name"
                                    placeholder="asd"
                                    required
                                    class="form lg"
                                />
                                <label for="name">Name</label>
                            </div>
                            <div class="form-feedback">Valid !</div>
                        </div>
                    </div>
                </Card>
                <Card title="Property Form">
                    <InputBasic
                        label="Disabled"
                        disabled
                        v-model="form.exampleInput"
                    />
                    <InputBasic
                        label="Readonly"
                        readonly
                        v-model="form.exampleInput"
                    />
                </Card>

                <Card title="Validation">
                    <InputBasic
                        placeholder="Name..."
                        label="Example Error"
                        error="Wajib diisi!"
                    />
                    <InputBasic
                        label="Example Success"
                        placeholder="email@domain.com"
                        success="Email valid !"
                    />
                </Card>

                <Card title="Form Horizontal">
                    <div class="flex flex-row gap-2 items-end">
                        <InputBasic
                            placeholder="Name..."
                            label="Example Error"
                        />
                        <InputBasic
                            label="Example Success"
                            placeholder="email@domain.com"
                        />
                        <div>
                            <button class="btn btn-main">Simpan</button>
                        </div>
                    </div>
                    <div class="flex flex-row gap-2 items-end pt-2">
                        <InputBasic
                            placeholder="Name..."
                            size="sm"
                            label="Example Error"
                        />
                        <InputBasic
                            label="Example Success"
                            size="sm"
                            placeholder="email@domain.com"
                        />
                        <div>
                            <button class="btn btn-main btn-sm">Simpan</button>
                        </div>
                    </div>
                </Card>
            </div>
            <div class="flex flex-col gap-4">
                <Card title="Input File">
                    <div class="flex flex-col gap-2">
                        <div>
                            <label for="example_file_sm"
                                >Input File Small</label
                            >
                            <input
                                type="file"
                                class="form sm"
                                id="example_file_sm"
                                readonly
                                placeholder="Example..."
                            />
                        </div>
                        <div>
                            <label for="example_file">Input File Regular</label>
                            <input
                                type="file"
                                class="form"
                                id="example_file"
                                readonly
                                placeholder="Example..."
                            />
                        </div>
                    </div>
                </Card>
                <Card title="Input Radio">
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
                    <div class="font-semibold text-lg my-2">Button Radio</div>
                    <div class="flex flex-row gap-2">
                        <div>
                            <input
                                type="radio"
                                class="form-check-btn peer"
                                name="button_radio"
                                id="button_radio1"
                            />
                            <label
                                class="btn btn-outline-main"
                                for="button_radio1"
                                >Button Radio</label
                            >
                        </div>

                        <div>
                            <input
                                type="radio"
                                class="form-check-btn peer"
                                name="button_radio"
                                id="button_radio2"
                                checked
                            />
                            <label
                                class="btn btn-outline-main"
                                for="button_radio2"
                                >Button Radio Checked</label
                            >
                        </div>
                    </div>
                </Card>

                <Card title="Input Checkbox">
                    <div class="flex flex-col gap-2">
                        <div>
                            <div class="form-check">
                                <input
                                    name="checkbox_default"
                                    type="checkbox"
                                    class="form-check-input"
                                    id="checkbox_default"
                                />
                                <label
                                    for="checkbox_default"
                                    class="form-check-label"
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
                                <label
                                    for="checkbox_default2"
                                    class="form-check-label"
                                    >Checkbox Default Checked</label
                                >
                            </div>
                        </div>
                        <div>
                            <div class="font-semibold text-lg mb-2">
                                Button Checkbox
                            </div>
                            <div class="flex flex-row gap-2">
                                <div>
                                    <input
                                        type="checkbox"
                                        class="form-check-btn peer"
                                        name="button_checkbox"
                                        id="button_check1"
                                    />
                                    <label
                                        class="btn btn-highlight-main"
                                        for="button_check1"
                                        >Button Checkbox</label
                                    >
                                </div>

                                <div>
                                    <input
                                        type="checkbox"
                                        class="form-check-btn peer"
                                        name="button_checkbox"
                                        id="button_checkbox2"
                                        checked
                                    />
                                    <label
                                        class="btn btn-highlight-main"
                                        for="button_checkbox2"
                                        >Button Checkbox Checked</label
                                    >
                                </div>
                            </div>
                        </div>
                        <div>
                            <div class="font-semibold text-lg mb-2">
                                Checkbox Switch
                            </div>
                            <div class="flex flex-col gap-2">
                                <Switch
                                    id="switch_regular"
                                    name="switch_regular"
                                    labeling="Example regular"
                                />
                            </div>
                        </div>
                    </div>
                </Card>

                <Card title="Form Group">
                    <div class="flex flex-col gap-2">
                        <div>
                            <div class="form-group has-text">
                                <label for="form_group" class="form-group-text">
                                    <FontAwesomeIcon :icon="faSearch" />
                                </label>
                                <input
                                    type="text"
                                    id="form_group"
                                    class="form"
                                />
                            </div>
                        </div>

                        <div class="form-group has-text">
                            <label
                                for="form_group_large"
                                class="form-group-text"
                            >
                                Large
                            </label>
                            <input id="form_group_large" class="form lg" />
                        </div>
                        <div>
                            <label>Group Input</label>
                            <div class="form-group">
                                <input
                                    type="text"
                                    class="form"
                                    placeholder="From ... "
                                />
                                <span> | </span>
                                <input
                                    type="text"
                                    class="form"
                                    placeholder="To ..."
                                />
                            </div>
                        </div>
                        <div>
                            <label>Group Input Date</label>
                            <div class="form-group">
                                <input
                                    type="date"
                                    class="form"
                                    placeholder="From"
                                />
                                <span>
                                    <FontAwesomeIcon :icon="faArrowRight" />
                                </span>
                                <input
                                    type="date"
                                    class="form"
                                    placeholder="To"
                                />
                            </div>
                        </div>
                        <div>
                            <label>Group Select</label>
                            <div class="form-group">
                                <select name="by" class="form">
                                    <option>Name</option>
                                    <option>Price</option>
                                    <option>Date</option>
                                </select>
                                <span> | </span>
                                <select name="sort" class="form">
                                    <option>Asc</option>
                                    <option>Desc</option>
                                </select>
                            </div>
                        </div>
                        <div>
                            <label for="form_group_btn">Group Button</label>
                            <div class="form-group">
                                <button class="btn btn-main">
                                    <FontAwesomeIcon :icon="faSearch" /> Cari
                                </button>
                                <input
                                    type="text"
                                    class="form"
                                    id="form_group_btn"
                                />
                            </div>
                        </div>
                    </div>
                </Card>
            </div>
        </div>
    </Container>
</template>
<script setup>
import CardTransparent from "@/Components/Dashboard/Cards/CardTransparent.vue";
import InputBasic from "@/Components/Dashboard/Form/InputBasic.vue";
import SelectBasic from "@/Components/Dashboard/Form/SelectBasic.vue";
import Switch from "@/Components/Dashboard/Form/Switch.vue";
import TextareaBasic from "@/Components/Dashboard/Form/TextareaBasic.vue";
import Card from "@/Components/Dashboard/UI/Card.vue";
import Container from "@/Components/Dashboard/UI/Container.vue";
import { faArrowRight, faSearch } from "@fortawesome/free-solid-svg-icons";
import { FontAwesomeIcon } from "@fortawesome/vue-fontawesome";
import { useForm } from "@inertiajs/vue3";
import { ref } from "vue";

const form = useForm({
    exampleInput: "Test",
    exampleTextarea: "",
    exampleSelect: "",
});
</script>
