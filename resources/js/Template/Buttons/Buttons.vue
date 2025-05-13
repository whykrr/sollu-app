<template>
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
                <button class="btn btn-outline-success">Button Outline</button>
                <button class="btn btn-outline-info">Button Outline</button>
                <button class="btn btn-outline-warning">Button Outline</button>
                <button class="btn btn-outline-danger">Button Outline</button>
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
            </div>
        </Card>

        <Card title="Button with Icon">
            <div class="flex flex-row gap-1 mb-2">
                <button class="btn btn-main">
                    <fa icon="fa-plus" />
                    Btn Icon
                </button>
                <button class="btn btn-secondary">
                    <fa icon="fa-plus" />
                    Btn Icon
                </button>
                <button class="btn btn-success">
                    <fa icon="fa-plus" />
                    Btn Icon
                </button>
                <button class="btn btn-info">
                    <fa icon="fa-plus" />
                    Btn Icon
                </button>
                <button class="btn btn-warning">
                    <fa icon="fa-plus" />
                    Btn Icon
                </button>
                <button class="btn btn-danger">
                    <fa icon="fa-plus" />
                    Btn Icon
                </button>
                <a href="#" class="btn link">
                    <fa icon="fa-plus" />
                    Btn Icon
                </a>
                <button class="btn" disabled>
                    <fa icon="fa-plus" />
                    Btn Icon
                </button>
            </div>

            <div class="flex flex-row gap-1">
                <button class="btn btn-outline-main">
                    <fa icon="fa-plus" />
                    Btn Icon
                </button>
                <button class="btn btn-outline-secondary">
                    <fa icon="fa-plus" />
                    Btn Icon
                </button>
                <button class="btn btn-outline-success">
                    <fa icon="fa-plus" />
                    Btn Icon
                </button>
                <button class="btn btn-outline-info">
                    <fa icon="fa-plus" />
                    Btn Icon
                </button>
                <button class="btn btn-outline-warning">
                    <fa icon="fa-plus" />
                    Btn Icon
                </button>
                <button class="btn btn-outline-danger">
                    <fa icon="fa-plus" />
                    Btn Icon
                </button>
            </div>
        </Card>

        <Card title="Button Block">
            <div class="flex flex-col gap-2">
                <button class="btn btn-main block">Button</button>
                <button class="btn btn-outline-main block">Button</button>
                <button class="btn btn-outline-main block">
                    <fa icon="fa-plus" />
                    Button
                </button>
            </div>
        </Card>
    </div>
</template>
<script setup>
import Card from "@/Components/UI/Card.vue";
</script>
