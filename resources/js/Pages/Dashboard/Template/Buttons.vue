<template>
    <Container>
        <div class="flex flex-col gap-4">
            <Card title="Button">
                <div class="flex flex-row gap-1">
                    <button class="btn btn-main">Button</button>
                    <button class="btn btn-secondary">Button</button>
                    <button class="btn btn-success">Button</button>
                    <button class="btn btn-info">Button</button>
                    <button class="btn btn-warning">Button</button>
                    <button class="btn btn-danger">Button</button>
                    <a href="#" class="btn link">Button</a>
                    <button class="btn" disabled>Button</button>
                </div>
            </Card>

            <Card title="Button Size">
                <button class="btn btn-xs btn-main mr-2">Extra Small</button>
                <button class="btn btn-sm btn-main mr-2">Small</button>
                <button class="btn btn-main mr-2">Regular</button>
                <button class="btn btn-lg btn-main">Large</button>
            </Card>

            <Card title="Button Outline">
                <div class="flex flex-row gap-1">
                    <button class="btn btn-outline-main">Button Outline</button>
                    <button class="btn btn-outline-secondary">
                        Button Outline
                    </button>
                    <button class="btn btn-outline-success">
                        Button Outline
                    </button>
                    <button class="btn btn-outline-info">Button Outline</button>
                    <button class="btn btn-outline-warning">
                        Button Outline
                    </button>
                    <button class="btn btn-outline-danger">
                        Button Outline
                    </button>
                </div>
            </Card>

            <Card title="Button Highlight">
                <div class="flex flex-row gap-1">
                    <button class="btn btn-highlight-main">Button</button>
                    <button class="btn btn-highlight-secondary">Button</button>
                    <button class="btn btn-highlight-success">Button</button>
                    <button class="btn btn-highlight-info">Button</button>
                    <button class="btn btn-highlight-warning">Button</button>
                    <button class="btn btn-highlight-danger">Button</button>
                    <button class="btn btn-highlight-red-600">Button</button>
                </div>
            </Card>

            <Card title="Button with Icon">
                <div class="flex flex-row gap-1 mb-2">
                    <button class="btn btn-main">
                        <FontAwesomeIcon :icon="faPlus" />
                        Btn Icon
                    </button>
                    <button class="btn btn-secondary">
                        <FontAwesomeIcon :icon="faPlus" />
                        Btn Icon
                    </button>
                    <button class="btn btn-success">
                        <FontAwesomeIcon :icon="faPlus" />
                        Btn Icon
                    </button>
                    <button class="btn btn-info">
                        <FontAwesomeIcon :icon="faPlus" />
                        Btn Icon
                    </button>
                    <button class="btn btn-warning">
                        <FontAwesomeIcon :icon="faPlus" />
                        Btn Icon
                    </button>
                    <button class="btn btn-danger">
                        <FontAwesomeIcon :icon="faPlus" />
                        Btn Icon
                    </button>
                    <a href="#" class="btn link">
                        <FontAwesomeIcon :icon="faPlus" />
                        Btn Icon
                    </a>
                    <button class="btn" disabled>
                        <FontAwesomeIcon :icon="faPlus" />
                        Btn Icon
                    </button>
                </div>

                <div class="flex flex-row gap-1">
                    <button class="btn btn-outline-main">
                        <FontAwesomeIcon :icon="faPlus" />
                        Btn Icon
                    </button>
                    <button class="btn btn-outline-secondary">
                        <FontAwesomeIcon :icon="faPlus" />
                        Btn Icon
                    </button>
                    <button class="btn btn-outline-success">
                        <FontAwesomeIcon :icon="faPlus" />
                        Btn Icon
                    </button>
                    <button class="btn btn-outline-info">
                        <FontAwesomeIcon :icon="faPlus" />
                        Btn Icon
                    </button>
                    <button class="btn btn-outline-warning">
                        <FontAwesomeIcon :icon="faPlus" />
                        Btn Icon
                    </button>
                    <button class="btn btn-outline-danger">
                        <FontAwesomeIcon :icon="faPlus" />
                        Btn Icon
                    </button>
                </div>
            </Card>

            <Card title="Button Block">
                <div class="flex flex-col gap-2">
                    <button class="btn btn-main block">Button</button>
                    <button class="btn btn-outline-main block">Button</button>
                    <button class="btn btn-outline-main block">
                        <FontAwesomeIcon :icon="faPlus" />
                        Button
                    </button>
                </div>
            </Card>
        </div>
    </Container>
</template>
<script setup>
import Card from "@/Components/Dashboard/UI/Card.vue";
import Container from "@/Components/Dashboard/UI/Container.vue";
import { faPlus } from "@fortawesome/free-solid-svg-icons";
import { FontAwesomeIcon } from "@fortawesome/vue-fontawesome";
</script>
