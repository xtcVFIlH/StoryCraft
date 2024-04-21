<template>
    <el-dialog
        v-model="isLocalDialogVisible"
        :fullscreen="true"
        @close="handleDialogClose"
        @open="handleDialogOpen"
        :before-close="handleBeforeDialogClose"
    >
        <div 
            class="story-info-editor-container"
            v-loading="isLoading"
        >
            <div 
                style="display: flex; align-items: center;"
            >
                <div style="flex-grow: 1; flex-shrink: 1;">
                    <el-select
                        v-model="storyIdForEdit"
                        placeholder="选择故事"
                    >
                        <el-option
                            label="添加新故事"
                            :value="defaultStoryId"
                        ></el-option>
                        <el-option
                            v-for="story in storyList"
                            :key="story.id"
                            :label="story.title"
                            :value="story.id"
                        >   
                        </el-option>
                    </el-select>
                </div>
                <div style="flex-grow: 0; flex-shrink: 0; box-sizing: border; padding: 0 10px;">
                    <el-button 
                        :icon="storyOperationBtnIcon" circle size="small"
                        @click="handleUploadBtnClick"
                    />
                </div>
            </div>
            <el-input
                v-model="storyInfoForEdit.title"
                placeholder="故事标题"
            ></el-input>
            <el-input
                type="textarea"
                resize="none"
                v-model="storyInfoForEdit.backgroundInfo"
                show-word-limit
                maxlength="900"
                :rows="8"
                placeholder="故事背景信息"
            ></el-input>
            <character-info-editor
                :character-infos="storyInfoForEdit.characterInfos"
                @update-character-infos="handleUpdateCharacterInfos"
            ></character-info-editor>
        </div>
    </el-dialog>
</template>

<script setup>

import CharacterInfoEditor from './story-info-editor/CharacterInfoEditor'
import { ref, watch, computed, onMounted } from 'vue'
import { useRequest } from '@/composables/useRequest';
import { Upload, Plus } from '@element-plus/icons-vue'
import { useLocalStorage } from '@/composables/useLocalStorage'
import { ElMessageBox, ElNotification } from 'element-plus'

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

const props = defineProps([
    'modelValue', // 绑定对话框的显示状态
])
const emits = defineEmits([
    'updateStoryId',
    'updateStoryInfo',
    'initialized',
    'update:modelValue',
])
const isLoading = ref(false);

/**
 * 中继对话框显示状态，与modelValue同步
 * @type {ref<boolean>}
 */
const isLocalDialogVisible = ref(false);
watch(() => props.modelValue, (newValue) => {
    isLocalDialogVisible.value = newValue;
})
watch(() => isLocalDialogVisible.value, (newValue) => {
    emits('update:modelValue', newValue);
})

/**
 * 当前故事的操作按钮图标
 * @type {import('vue').ComputedRef<Plus|Upload>}
 */
const storyOperationBtnIcon = computed(() => {
    return localStoryId.value === defaultStoryId ? Plus : Upload;
})

const defaultStoryId = -1;
const defaultStoryInfo = {
    title: '',
    backgroundInfo: '',
    characterInfos: [],
};

const localStoryIdMiddleware = useLocalStorage('storyId', null);
/**
 * 本地故事Id，故事信息编辑器处于隐藏时该值必定与父组件一致（除了初始化阶段）
 * 同步到父组件时，defaultStoryId会被转换为null
 * @type {ref<number>}
 */
 const localStoryId = ref(localStoryIdMiddleware.value === null ? defaultStoryId : localStoryIdMiddleware.value);
 watch(() => localStoryId.value, (newValue) => {
    localStoryIdMiddleware.value = newValue === defaultStoryId ? null : newValue;
 })
/**
 * 本地故事信息，故事信息编辑器处于隐藏时该值必定与父组件一致（除了初始化阶段）
 * @type {ref<object>}
 */
const localStoryInfo = ref({ ...defaultStoryInfo });
/**
 * 用于编辑的故事Id
 * @type {ref<number>}
 */
const storyIdForEdit = ref(localStoryId.value);
/**
 * 用于编辑的故事信息
 * @type {ref<object>}
 */
const storyInfoForEdit = ref({ ...defaultStoryInfo });

// storyIdForEdit变化时，尝试更新故事信息，失败则回滚
watch(() => storyIdForEdit.value, (newValue) => {
    if (newValue === localStoryId.value) {
        storyInfoForEdit.value = JSON.parse(JSON.stringify(localStoryInfo.value));
        return ;
    }
    if (newValue === defaultStoryId) {
        storyInfoForEdit.value = { ...defaultStoryInfo };
        return ;
    }
    getStoryInfo(newValue)
    .then((data) => {
        localStoryId.value = newValue;
        localStoryInfo.value = { ...data };
        storyInfoForEdit.value = { ...data };
    })
    .catch((error) => {
        alertError('获取故事信息失败: ' + (typeof error === 'string' ? error : '未知错误'));
        storyIdForEdit.value = localStoryId.value;
    });
})

const updateStoryData = () => {
    emits('updateStoryId', localStoryId.value === defaultStoryId ? null : localStoryId.value);
    emits('updateStoryInfo', localStoryInfo.value);
}
const handleDialogClose = () => {
    updateStoryData();
}
const handleDialogOpen = () => {
    storyIdForEdit.value = localStoryId.value;
}

/**
 * 获取对应id的故事信息
 * @param {number} storyId
 * @returns {Promise<object>} 故事信息
 */
const getStoryInfo = storyId => {
    if (storyId === defaultStoryId) {
        return Promise.resolve(defaultStoryInfo);
    }
    isLoading.value = true;
    return request('/story/get-story-info', {
        storyId: storyId,
    })
    .finally(() => {
        isLoading.value = false;
    });
}

const handleBeforeDialogClose = (done) => {
    // 检查是否有未上传的更改
    if (localStoryId.value === storyIdForEdit.value && JSON.stringify(localStoryInfo.value) === JSON.stringify(storyInfoForEdit.value)) {
        done();
        return ;
    }
    // 提示用户是否保存、或直接关闭编辑器对话框（这将丢弃未保存的更改）
    ElMessageBox.confirm(
        '有未保存的更改。', 
        '提示', 
        {
            confirmButtonText: '保存并退出',
            cancelButtonText: '不保存并退出',
            distinguishCancelAndClose: true,
            type: 'warning',
        }
    )
    .then(() => {
        return uploadStoryInfo()
        .then(() => {
            done();
        });
    })
    .catch((action) => {
        if (action === 'cancel') {
            // 丢弃未保存的更改
            storyIdForEdit.value = localStoryId.value;
            storyInfoForEdit.value = JSON.parse(JSON.stringify(localStoryInfo.value));
            done();
        }
    });
}

/**
 * 当前可选择的故事列表
 * @type {ref<Array<object>>}
 */
const storyList = ref([]);
/**
 * 刷新可选择的故事列表
 * @returns 
 */
const refreshStoryList = () => {
    isLoading.value = true;
    return request('/story/get-stories-with-id-and-title')
    .then((data) => {
        storyList.value = data.storiesWithIdAndTitle;
        if (!storyList.value.find(story => story.id === storyIdForEdit.value)) {
            storyIdForEdit.value = defaultStoryId;
        }
        return Promise.resolve();
    })
    .catch((error) => {
        alertError('获取故事列表失败: ' + (typeof error === 'string' ? error : '未知错误'));
        storyIdForEdit.value = defaultStoryId;
        return Promise.reject();
    })
    .finally(() => {
        isLoading.value = false;
    });
}

/**
 * 向服务器更新当前的故事信息
 * @returns {void}
 */
const uploadStoryInfo = () => {
    isLoading.value = true;
    return request('/upload/story-info', {
        storyId: storyIdForEdit.value === defaultStoryId ? null : storyIdForEdit.value,
        storyInfo: storyInfoForEdit.value,
    })
    .then((data) => {
        alertSuccess('更新故事成功');
        localStoryId.value = data.storyId;
        storyIdForEdit.value = data.storyId;
        localStoryInfo.value = { ...storyInfoForEdit.value };
        storyList.value = data.storiesWithIdAndTitle;
        return Promise.resolve();
    })
    .catch((error) => {
        alertError('更新故事失败: ' + (typeof error === 'string' ? error : '未知错误'));
        return Promise.reject();
    })
    .finally(() => {
        isLoading.value = false;
    });
}
const handleUploadBtnClick = () => {
    uploadStoryInfo()
    .catch(() => {});
}

const handleUpdateCharacterInfos = (newValue) => {
    storyInfoForEdit.value.characterInfos = JSON.parse(JSON.stringify(newValue));
}

onMounted(() => {
    refreshStoryList()
    .then(() => {
        return getStoryInfo(localStoryId.value);
    })
    .then((data) => {
        localStoryInfo.value = { ...data };
        storyInfoForEdit.value = { ...data };
    })
    .catch(() => {
        localStoryId.value = defaultStoryId;
        localStoryInfo.value = { ...defaultStoryInfo };
        storyIdForEdit.value = defaultStoryId;
        storyInfoForEdit.value = { ...defaultStoryInfo };
    })
    .finally(() => {
        updateStoryData();
        emits('initialized');
    });
})

</script>

<style scoped>
.story-info-editor-container {
    box-sizing: border-box;
    padding: 10px;
}
.story-info-editor-container>*:not(:last-child) {
    margin-bottom: 10px;
}
</style>