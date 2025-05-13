<template>
    <div class="tab-vertical">
        <ul role="tablist">
            <slot name="navigation" />
        </ul>
        <div class="tab-content">
            <slot></slot>
        </div>
    </div>
</template>
<script setup></script>
