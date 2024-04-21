<template>
    <div class="container">
        <div class="input-container">
            <el-input
                type="textarea"
                :rows="5"
                resize="none"
                v-model="prompt"
                :autosize="{ minRows: 1, maxRows: 5 }"
                placeholder=""
                @keydown.enter="handleSubmit"
                @input="handleInput"
            ></el-input>
        </div>
        <div class="btn-container">
            <el-button
                type="primary"
                :icon="Upload"
                @click="handleSubmit"
                size="small"
                circle
            >
            </el-button>
        </div>
    </div>
</template>
  
<script setup>
import { ref, watch } from 'vue'
import { ElNotification } from 'element-plus'
import { Upload } from '@element-plus/icons-vue'

const props = defineProps([
    'userPromptClearCount',
])

const alertError = error => {
    ElNotification({
        title: '错误',
        message: error,
        type: 'error',
    });
}

const prompt = ref('')

const emit = defineEmits(['submit'])

watch(() => props.userPromptClearCount, () => {
    prompt.value = ''
})

const handleSubmit = () => {
    const promptEntered = prompt.value.trim();
    if (!promptEntered) {
        alertError('用户提示词不能为空');
        return;
    }
    if (promptEntered.includes('---') || promptEntered.includes('```')) {
        alertError('用户提示词不能包含---、```');
        return;
    }
    emit('submit', promptEntered)
}

const handleInput = (value) => {
    value = value.replace(/[\r\n]+/g, '');

    if (value.length > 200) {
        value = value.slice(0, 200);
    }

    prompt.value = value;
};
</script>

<style scoped>
.container {
    box-sizing: border-box;
    display: flex;
    align-items: center;
    padding: 5px 0 0 0;
}
.input-container {
    flex-grow: 1;
    flex-shrink: 1;
}
.btn-container {
    flex-grow: 0;
    flex-shrink: 0;
    box-sizing: border;
    margin: 0 0 0 5px;

}
</style>