<template>
    <div class="container">
        <el-input
            type="textarea"
            :rows="2"
            resize="none"
            v-model="prompt"
            placeholder="输入新故事情节，按回车键发送"
            @keydown.enter="handleSubmit"
            @input="handleInput"
            :inputStyle="{ borderRadius: 0 }"
        ></el-input>
    </div>
</template>
  
<script setup>
import { ref, watch } from 'vue'

const props = defineProps([
    'userPromptClearCount',
])

const prompt = ref('')

const emit = defineEmits(['submit'])

watch(() => props.userPromptClearCount, () => {
    prompt.value = ''
})

const handleSubmit = () => {
    const promptEntered = prompt.value.trim();
    if (!promptEntered) {
        return;
    }
    emit('submit', promptEntered)
}

const handleInput = value => {
    prompt.value = value.replace(/[\r\n]/g, '')
}
</script>

<style scoped>
</style>