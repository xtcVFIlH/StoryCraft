<template>
    <el-dialog
        v-model="isShow"
        :fullscreen="true"
        @open="loadInfo"
    >
        <div 
            class="container"
            v-loading="isLoading"
        >
            <el-form
                label-width="auto"
                label-position="top"
                @submit.prevent
            >
                <el-form-item label="会话标题">
                    <el-input 
                        v-model="title"
                    />
                </el-form-item>
                <el-form-item label="会话额外信息">
                    <el-input
                        type="textarea"
                        :placeholder="customInstructionsPlaceHolder"
                        :rows="8"
                        v-model="customInstructions"
                    />
                </el-form-item>
                <el-form-item>
                    <el-button 
                        type="primary"
                        @click="submit"
                        :disabled="submitBtnDisabled"
                    >
                        {{ isCreateOperation ? '新建会话' : '更新会话' }}
                    </el-button>
                </el-form-item>
            </el-form>
        </div>
    </el-dialog>
</template>

<script setup>

import { ref, watch, computed } from 'vue'
import { ElNotification } from 'element-plus'
import { useRequest } from '@/composables/useRequest'

const { request } = useRequest();

const alertError = error => {
    ElNotification({
        title: '错误',
        message: error,
        type: 'error',
    });
}
const alertSuccess = message => {
    ElNotification({
        title: '成功',
        message: message,
        type: 'success',
    });
}

const customInstructionsPlaceHolder = `输入额外的故事背景信息、故事风格要求等等...
如：
- A和B是朋友关系
- 故事风格为悬疑`;

const props = defineProps([
    'modelValue',
    'chatSessionId',
    'storyId',
])
const emits = defineEmits([
    'update:modelValue',
    'updateChatSessionInfo',
])

const isShow = ref(false);
watch(() => props.modelValue, (newValue) => {
    isShow.value = newValue;
})
watch(() => isShow.value, (newValue) => {
    emits('update:modelValue', newValue);
})

const title = ref('');
const customInstructions = ref('');

const isLoading = ref(false);

const submitBtnDisabled = computed(() => {
    return !props.storyId || !title.value;
})
const isCreateOperation = computed(() => {
    return props.chatSessionId === null || props.chatSessionId === undefined;
})
const submit = () => {
    isLoading.value = true;
    request(
        '/chat-session/update',
        {
            storyId: props.storyId,
            chatSessionId: props.chatSessionId,
            title: title.value,
            customInstructions: customInstructions.value,
        }
    )
    .then(data => {
        emits('updateChatSessionInfo', {
            id: data.chatSessionId,
            title: data.title,
        });
        alertSuccess('更新成功');
    })
    .catch(error => {
        alertError(typeof error === 'string' ? error : '未知错误');
    })
    .finally(() => {
        isLoading.value = false;
    })
}

const loadInfo = () => {
    title.value = '';
    customInstructions.value = '';

    if (!props.chatSessionId || !props.storyId) {
        return;
    }

    isLoading.value = true;
    request('/chat-session/get-one', {
        storyId: props.storyId,
        chatSessionId: props.chatSessionId,
    })
    .then(data => {
        title.value = data.title;
        customInstructions.value = data.customInstructions;
    })
    .catch(error => {
        alertError(typeof error === 'string' ? error : '未知错误');
        emits('update:modelValue', false);
    })
    .finally(() => {
        isLoading.value = false;
    })
}

</script>

<style scoped>
.container {
    box-sizing: border-box;
    padding: 10px;
}
</style>