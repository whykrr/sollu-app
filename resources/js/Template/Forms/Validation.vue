<template>
    <div class="flex flex-col gap-4">
        <Card title="Success Example">
            <div class="grid grid-cols-6 gap-2">
                <div class="col-span-2">
                    <label for="success_name">Name</label>
                    <input
                        type="text"
                        name="success_name"
                        id="success_name"
                        class="form is-valid"
                    />
                    <span class="form-feedback">Looks Good!</span>
                </div>
                <div class="col-span-2">
                    <label for="success_email">Email</label>
                    <input
                        type="email"
                        name="success_email"
                        id="success_email"
                        class="form is-valid"
                    />
                    <span class="form-feedback">Looks Good!</span>
                </div>
                <div class="col-span-2">
                    <label for="success_id">ID Number</label>
                    <input
                        type="text"
                        name="success_id"
                        id="success_id"
                        class="form is-valid"
                    />
                    <span class="form-feedback">Looks Good!</span>
                </div>
                <div class="col-span-2">
                    <label for="success_nation">Nationality</label>
                    <select
                        name="success_nation"
                        id="success_nation"
                        class="form is-valid"
                    >
                        <option>-- Choose --</option>
                        <option>Indonesia</option>
                        <option>United State</option>
                    </select>
                    <span class="form-feedback">Looks Good!</span>
                </div>
                <div class="col-span-4">
                    <label for="success_address">Address</label>
                    <textarea
                        name="success_address"
                        id="success_address"
                        class="form is-valid"
                    ></textarea>
                    <span class="form-feedback">Looks Good!</span>
                </div>
                <div class="col-span-2">
                    <label for="success_file">File</label>
                    <input
                        type="file"
                        name="success_file"
                        id="success_file"
                        class="form is-valid"
                    />
                    <span class="form-feedback">Looks Good!</span>
                </div>
                <div class="col-span-2">
                    <label>Search</label>
                    <div class="form-group is-valid">
                        <span for="form_group">
                            <fa icon="fa-search" />
                        </span>
                        <input type="text" id="form_group" class="form" />
                    </div>
                    <span class="form-feedback">Looks Good!</span>
                </div>
                <div class="col-span-2">
                    <label>Example Floating</label>
                    <div class="form-floating is-valid">
                        <input
                            type="text"
                            id="name"
                            placeholder="asd"
                            required
                            class="form"
                        />
                        <label for="name">Name</label>
                    </div>
                    <span class="form-feedback">Looks Good!</span>
                </div>
            </div>
        </Card>
        <Card title="Failed Example">
            <div class="grid grid-cols-6 gap-2">
                <div class="col-span-2">
                    <label for="failed_name">Name</label>
                    <input
                        type="text"
                        name="failed_name"
                        id="failed_name"
                        class="form is-invalid"
                    />
                    <span class="form-feedback">Looks Bad!</span>
                </div>
                <div class="col-span-2">
                    <label for="failed_email">Email</label>
                    <input
                        type="email"
                        name="failed_email"
                        id="failed_email"
                        class="form is-invalid"
                    />
                    <span class="form-feedback">Looks Bad!</span>
                </div>
                <div class="col-span-2">
                    <label for="failed_id">ID Number</label>
                    <input
                        type="text"
                        name="failed_id"
                        id="failed_id"
                        class="form is-invalid"
                    />
                    <span class="form-feedback">Looks Bad!</span>
                </div>
                <div class="col-span-2">
                    <label for="failed_nation">Nationality</label>
                    <select
                        name="failed_nation"
                        id="failed_nation"
                        class="form is-invalid"
                    >
                        <option>-- Choose --</option>
                        <option>Indonesia</option>
                        <option>United State</option>
                    </select>
                    <span class="form-feedback">Looks Bad!</span>
                </div>
                <div class="col-span-4">
                    <label for="failed_address">Address</label>
                    <textarea
                        name="failed_address"
                        id="failed_address"
                        class="form is-invalid"
                    ></textarea>
                    <span class="form-feedback">Looks Bad!</span>
                </div>
                <div class="col-span-2">
                    <label for="failed_file">File</label>
                    <input
                        type="file"
                        name="failed_file"
                        id="failed_file"
                        class="form is-invalid"
                    />
                    <span class="form-feedback">Looks Bad!</span>
                </div>
                <div class="col-span-2">
                    <label>Search</label>
                    <div class="form-group is-invalid">
                        <span for="failed_form_group">
                            <fa icon="fa-search" />
                        </span>
                        <input
                            type="text"
                            id="failed_form_group"
                            class="form"
                        />
                    </div>
                    <span class="form-feedback">Looks Bad!</span>
                </div>
                <div class="col-span-2">
                    <label>Example Floating</label>
                    <div class="form-floating is-invalid">
                        <input
                            type="text"
                            id="failed_floating"
                            placeholder="asd"
                            required
                            class="form"
                        />
                        <label for="failed_floating">Name</label>
                    </div>
                    <span class="form-feedback">Looks Bad!</span>
                </div>
            </div>
        </Card>
    </div>
</template>
<script setup>
import Card from "@/Components/UI/Card.vue";
</script>
