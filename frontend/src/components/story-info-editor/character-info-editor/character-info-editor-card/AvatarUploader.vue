<template>
    <el-upload
        class="uploader"
        :action="uploadAction"
        :show-file-list="false"
        :on-success="handleAvatarUploadSuccess"
        :on-error="handleAvatarUploadError"
        :before-upload="beforeAvatarUpload"
    >
        <el-image 
            v-if="props.modelValue" 
            :src="imageUrl"
            class="avatar" fit="contain"
        />
        <div class="uploader-button" v-else>
            <span>+</span>
        </div>
    </el-upload>
</template>
  
<script setup>
import { ref, computed } from 'vue'
import { ElNotification } from 'element-plus'
import { getFullImageUrl } from '@/utils/image';

const alertError = error => {
    ElNotification({
        title: '错误',
        message: error,
        type: 'error',
    });
}

const props = defineProps([
    'modelValue',
])

const emits = defineEmits([
    'update:modelValue',
])

const uploadAction = ref(process.env.VUE_APP_BACKEND_URI + '/upload/character-avatar')

const imageUrl = computed(() => {
    return getFullImageUrl(props.modelValue);
})

const updateModelValue = (newValue) => {
    emits('update:modelValue', newValue);
}
const handleAvatarUploadSuccess = (response) => {
    if (response.code !== 0) {
        alertError(response.message);
        return;
    }
    updateModelValue(response.data.avatarFileName);
}

const handleAvatarUploadError = () => {
    alertError('上传失败');
}

const beforeAvatarUpload = (rawFile) => {
    if (rawFile.type !== 'image/png' && rawFile.type !== 'image/jpeg' && rawFile.type !== 'image/jpg') {
        alertError('只能上传 .png, .jpg, .jpeg 格式的图片');
        return false;
    }
    if (rawFile.size > 3000 * 1024) {
        alertError('图片大小不能超过 3000kb');
        return false;
    }
    return true;
}
</script>

<style scoped>

    .uploader {
        display: block;
        position: relative;
    }

    .avatar {
        width: 50px;
        height: 50px;
        border-radius: 6px;
    }

    .uploader-button {
        width: 50px;
        height: 50px;
        box-sizing: border-box;
        border: 1px dashed var(--el-border-color);
        border-radius: 6px;
        color: #8c939d;
        cursor: pointer;
        display: flex;
        justify-content: center;
        align-items: center;
        transition: var(--el-transition-duration-fast);
    }

    .uploader-button:hover {
        border-color: var(--el-color-primary);
        color: var(--el-color-primary);
    }
    
</style>