<template>
    <div class="flex flex-col gap-4">
        <Card title="Basic Example">
            <div class="flex flex-col gap-2">
                <div>
                    <label for="basic_example_label">Example Label</label>
                    <input
                        type="text"
                        name="basic_example_label"
                        id="basic_example_label"
                        class="form"
                    />
                </div>
                <div>
                    <label for="basic_another_label">Another Label</label>
                    <input
                        type="text"
                        name="basic_another_label"
                        id="basic_another_label"
                        class="form"
                    />
                </div>
            </div>
        </Card>
        <Card title="Layout Grid">
            <div class="grid grid-cols-12 gap-2">
                <div class="col-span-4">
                    <label for="grid_first_name">First Name</label>
                    <input
                        type="text"
                        name="grid_first_name"
                        id="grid_first_name"
                        class="form"
                    />
                </div>
                <div class="col-span-4">
                    <label for="grid_middle_name">Middle Name</label>
                    <input
                        type="text"
                        name="grid_middle_name"
                        id="grid_middle_name"
                        class="form"
                    />
                </div>
                <div class="col-span-4">
                    <label for="grid_last_name">Last Name</label>
                    <input
                        type="text"
                        name="grid_last_name"
                        id="grid_last_name"
                        class="form"
                    />
                </div>
                <div class="col-span-8">
                    <label for="grid_identity_num">ID Number</label>
                    <input
                        type="text"
                        name="grid_identity_num"
                        id="grid_identity_num"
                        class="form"
                    />
                </div>
                <div class="col-span-4">
                    <label for="grid_address">Address</label>
                    <textarea
                        name="grid_address"
                        id="grid_address"
                        class="form"
                    ></textarea>
                </div>
            </div>
        </Card>
        <Card title="Horizontal Form">
            <div class="grid grid-cols-5 gap-2">
                <label for="horizontal_email" class="py-1.5">Email</label>
                <div class="col-span-4">
                    <input
                        type="text"
                        name="horizontal_email"
                        id="horizontal_email"
                        class="form w-full"
                        placeholder="example@site.com"
                    />
                </div>

                <label for="horizontal_password" class="py-1.5">Password</label>
                <div class="col-span-4">
                    <input
                        type="password"
                        name="horizontal_password"
                        id="horizontal_password"
                        class="form w-full"
                        placeholder="Insert your password"
                    />
                </div>

                <label for="horizontal_radio">Choose Radio</label>
                <div class="col-span-4">
                    <div class="form-check">
                        <input
                            type="radio"
                            name="horizontal_radio"
                            id="horizontal_radio1"
                            class="form-check-input"
                        />
                        <label for="horizontal_radio1" class="form-check-label"
                            >Radio 1</label
                        >
                    </div>

                    <div class="form-check">
                        <input
                            type="radio"
                            name="horizontal_radio"
                            id="horizontal_radio2"
                            class="form-check-input"
                        />
                        <label for="horizontal_radio2" class="form-check-label"
                            >Radio 2</label
                        >
                    </div>

                    <div class="form-check">
                        <input
                            type="radio"
                            name="horizontal_radio"
                            id="horizontal_radio3"
                            class="form-check-input"
                        />
                        <label for="horizontal_radio3" class="form-check-label"
                            >Radio 3</label
                        >
                    </div>
                </div>
            </div>
        </Card>
        <Card title="Auto Sizing">
            <div class="flex flex-row gap-2 items-center">
                <div class="flex-1">
                    <div class="form-group">
                        <span for="search">
                            <fa icon="fa-search" />
                        </span>
                        <input
                            type="text"
                            name="search"
                            id="search"
                            class="form"
                            placeholder="Search"
                        />
                    </div>
                </div>

                <div class="form-group">
                    <span>From</span>
                    <input type="date" class="form" />
                    <span>to</span>
                    <input type="date" class="form" />
                </div>

                <div>
                    <label>Sort by</label>
                </div>
                <div class="form-group-input">
                    <select class="form">
                        <option>Name</option>
                        <option>Total Transaction</option>
                        <option selected>Status</option>
                    </select>
                    <select class="form">
                        <option>Asc</option>
                        <option>Desc</option>
                    </select>
                </div>
                <div>
                    <button type="button" class="btn btn-main">Filter</button>
                </div>
            </div>
        </Card>
        <Card title="Inline Form">
            <div class="flex flex-row gap-2 items-center mb-4">
                <div class="form-group">
                    <span for="search_inline">
                        <fa icon="fa-search" />
                    </span>
                    <input
                        type="text"
                        name="search_inline"
                        id="search_inline"
                        class="form"
                        placeholder="Search"
                    />
                </div>

                <div class="form-group-input">
                    <select class="form">
                        <option>Name</option>
                        <option>Total Transaction</option>
                        <option selected>Status</option>
                    </select>
                    <select class="form">
                        <option>One</option>
                        <option>Two</option>
                        <option>Three</option>
                    </select>
                </div>
                <div class="form-check">
                    <input
                        type="checkbox"
                        name="deleted_inline"
                        id="deleted_inline"
                        class="form-check-input"
                    />
                    <label for="deleted_inline" class="form-check-label"
                        >Deleted</label
                    >
                </div>
                <div>
                    <button type="button" class="btn btn-main">Filter</button>
                </div>
            </div>
        </Card>
    </div>
</template>
<script setup>
import Card from "@/Components/UI/Card.vue";
</script>
