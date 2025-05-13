<template>
    <div class="flex flex-col gap-4">
        <Card title="Basic Example">
            <div class="flex flex-col gap-2">
                <div class="form-group">
                    <label for="form_group">
                        <fa icon="fa-search" />
                    </label>
                    <input type="text" id="form_group" class="form" />
                </div>
            </div>
        </Card>
        <Card title="Sizing">
            <div class="flex flex-col gap-2">
                <div class="form-group">
                    <label for="form_group_small" class="text-sm">
                        Small
                    </label>
                    <input type="text" id="form_group_small" class="form-sm" />
                </div>

                <div class="form-group">
                    <label for="form_group_regular"> Regular </label>
                    <input id="form_group_regular" class="form" />
                </div>

                <div class="form-group">
                    <label for="form_group_large" class="text-lg">
                        Large
                    </label>
                    <input id="form_group_large" class="form-lg" />
                </div>
            </div>
        </Card>
        <Card title="Form Group Input & Select">
            <div class="flex flex-col gap-2">
                <div>
                    <label>Filter Range</label>
                    <div class="form-group-input">
                        <input type="text" class="form" placeholder="From" />
                        <input type="text" class="form" placeholder="To" />
                    </div>
                </div>
                <div>
                    <label>Sort</label>
                    <div class="form-group-input">
                        <select name="by" class="form">
                            <option>Name</option>
                            <option>Price</option>
                            <option>Date</option>
                        </select>
                        <select name="sort" class="form">
                            <option>Asc</option>
                            <option>Desc</option>
                        </select>
                    </div>
                </div>
            </div>
        </Card>
        <Card title="Form Group Button">
            <div class="flex flex-col gap-2">
                <div class="form-group-btn">
                    <button class="btn btn-main">
                        <fa icon="fa-search" />
                    </button>
                    <input type="text" class="form" />
                </div>
                <div class="form-group-btn">
                    <input type="text" class="form" />
                    <button class="btn btn-main">
                        <fa icon="fa-search" />
                    </button>
                </div>
            </div>
        </Card>
    </div>
</template>
<script setup>
import Card from "@/Components/UI/Card.vue";
</script>
