<template>
    <div class="relative">
        <div ref="editor" class="form rounded-t-none overflow-visible!" />
    </div>
</template>
<script setup>
import Quill from 'quill';
import 'quill/dist/quill.snow.css';
import { onMounted, ref, watch } from 'vue';

const toolbarOptions = [
    [{ header: [1, 2, 3, false] }],
    [{ align: [] }],
    ''[('bold', 'italic', 'underline')],
    ['blockquote', 'code-block'],
    [{ list: 'ordered' }, { list: 'bullet' }],
    ['link'],
    ['clean'],
];

const props = defineProps({
    modelValue: {
        type: String,
        default: '',
    },
    placeholder: String,
});

// Emit
const emit = defineEmits(['update:modelValue']);

const editor = ref('null');
let quill = null;

onMounted(() => {
    quill = new Quill(editor.value, {
        theme: 'snow',
        modules: {
            toolbar: {
                container: toolbarOptions,
                handlers: {
                    image: imageHandler,
                },
            },
        },
        placeholder: props.placeholder,
    });

    // Set initial value props
    quill.root.innerHTML = props.modelValue;

    // Emit event when content changes
    quill.on('text-change', () => {
        emit('update:modelValue', quill.root.innerHTML);
    });
});

// Watch value prop to sync content
watch(
    () => props.modelValue,
    (newValue) => {
        if (quill && quill.root.innerHTML !== newValue) {
            quill.root.innerHTML = newValue;
        }
    },
);

function imageHandler() {
    const input = document.createElement('input');
    input.setAttribute('type', 'file');
    input.setAttribute('accept', 'image/*');

    input.click();

    input.onchange = async () => {
        const file = input.files[0];
        if (file) {
            const formData = new FormData();
            formData.append('image', file);

            try {
                const response = await fetch('/api/upload', {
                    method: 'POST',
                    body: formData,
                });

                if (response.ok) {
                    const result = await response.json();
                    const imageUrl = result.url;

                    const range = quill.getSelection();
                    quill.insertEmbed(range.index, 'image', imageUrl);
                } else {
                    console.error('Upload failed.');
                }
            } catch (error) {
                console.error('Error uploading image:', error);
            }
        }
    };
}
</script>
