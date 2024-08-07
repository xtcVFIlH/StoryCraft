<template>
    <div 
        id="app"
        v-loading.fullscreen.lock="isLoadingVisible"
    >
        <div class="app">
            <div class="header">
                <main-page-header
                    :chat-session-title="chatSessionInfo.title"
                    @enter-chat-session-bar="isChatSessionBarVisible = true"
                    @enter-story-info-editor="isStoryInfoEditorVisible = true"
                    @enter-chat-session-info-editor="isChatSessionInfoEditorVisible = true"
                >
                </main-page-header>
            </div>
            <div class="content">
                <div class="story-generate-area-container">
                    <story-generate-area
                        :story-contents="storyContents"
                        :character-infos="storyInfo.characterInfos"
                        :userPromptClearCount="userPromptClearCount"
                        @submit-user-prompt="generateStory"
                        @delete-user-story-content="handleDeleteUserStoryContent"
                        @edit-model-story-content="handleEditModelStoryContent"
                        @delete-model-story-content="handleDeleteModelStoryContent"
                    >
                    </story-generate-area>
                </div>
            </div>
        </div>
        <story-info-editor-dialog
            v-model="isStoryInfoEditorVisible"
            @update-story-id="storyId = $event"
            @update-story-info="handleUpdateStoryInfo"
            @initialized="isStoryInfoEditorInitialized = true"
        ></story-info-editor-dialog>
        <chat-session-vertical-bar
            v-model="isChatSessionBarVisible"
            :story-id="storyId"
            :chat-session-id="chatSessionInfo.id"
            :chat-session-title="chatSessionInfo.title"
            @update-chat-session-info="updateChatSessionInfo"
            @sessions-loading-start="loadingCount++"
            @sessions-loading-end="loadingCount--"
            @create-new-chat-session="isChatSessionInfoEditorVisible = true"
        >
        </chat-session-vertical-bar>
        <chat-session-info-editor-dialog
            v-model="isChatSessionInfoEditorVisible"
            :story-id="storyId"
            :chat-session-id="chatSessionInfo.id"
            @update-chat-session-info="updateChatSessionInfo"
        >
        </chat-session-info-editor-dialog>
        <frontend-proxy />
    </div>
</template>

<script setup>
import MainPageHeader from './components/MainPageHeader.vue'
import StoryGenerateArea from './components/StoryGenerateArea.vue'
import StoryInfoEditorDialog from './components/StoryInfoEditorDialog.vue'
import ChatSessionVerticalBar from './components/ChatSessionVerticalBar.vue'
import ChatSessionInfoEditorDialog from './components/ChatSessionInfoEditorDialog.vue'
import FrontendProxy from './components/FrontendProxy.vue'
import { ElMessageBox, ElNotification } from 'element-plus'

import { ref, watch } from 'vue'
import { useRequest } from '@/composables/useRequest'

const { request } = useRequest();

const alertError = error => {
    ElNotification({
        title: '错误',
        message: error,
        type: 'error',
    });
}

const isChatSessionInfoEditorVisible = ref(false);

const loadingCount = ref(1);
const isLoadingVisible = ref(true);
let loadingTimeoutId = null;
watch(() => loadingCount.value, (newValue) => {
    if (newValue > 0) {
        clearTimeout(loadingTimeoutId);
        loadingTimeoutId = null;
        isLoadingVisible.value = true;
    } else {
        loadingTimeoutId = setTimeout(() => {
            isLoadingVisible.value = false;
            loadingTimeoutId = null;
        }, 200);
    }
});

const isStoryInfoEditorInitialized = ref(false);
watch(isStoryInfoEditorInitialized, (newValue) => {
    if (newValue) {
        loadingCount.value = 0;
    }
})

const isStoryInfoEditorVisible = ref(false);

const storyId = ref(null);
const storyInfo = ref({
    title: '',
    backgroundInfo: '',
    characterInfos: [],
});
const handleUpdateStoryInfo = info => {
    storyInfo.value = JSON.parse(JSON.stringify(info));
}

const isChatSessionBarVisible = ref(false);

const chatSessionInfo = ref({
    id: null,
    title: '',
});
const updateChatSessionInfo = (data) => {
    if (Object.prototype.hasOwnProperty.call(data, 'id')) {
        chatSessionInfo.value.id = data.id;
    }
    if (Object.prototype.hasOwnProperty.call(data, 'title')) {
        chatSessionInfo.value.title = data.title;
    }
}
watch(() => chatSessionInfo.value.id, () => {
    refreshStory();
})

const storyContents = ref([]);

const handleDeleteUserStoryContent = recordId => {
    ElMessageBox.confirm('确定删除这条输入、以及对应的模型输出吗？', '删除', {
        confirmButtonText: '删除',
        cancelButtonText: '取消',
        type: 'warning',
    })
    .then(() => {
        loadingCount.value++;
        return request('/story/delete-user-story-content', {
            recordId,
            chatSessionId: chatSessionInfo.value.id,
        })
        .then((data) => {
            const deletedRecordIds = data.deletedRecordIds;
            storyContents.value = storyContents.value.filter(content => !deletedRecordIds.includes(content.id));
        })
        .finally(() => {
            loadingCount.value--;
        })
    })
    .catch((error) => {
        if (error === 'cancel') {
            return;
        }
        alertError('删除故事片段失败: ' + (typeof error === 'string' ? error : '未知错误'));
    });
}

const refreshStory = () => {
    if (!storyId.value || !chatSessionInfo.value.id) {
        storyContents.value = [];
        return;
    }
    loadingCount.value++;
    request('/story/get-all-story-contents', {
        storyId: storyId.value,
        chatSessionId: chatSessionInfo.value.id,
    })
    .then((data) => {
        storyContents.value = data;
    })
    .catch((error) => {
        alertError('获取故事内容失败: ' + (typeof error === 'string' ? error : '未知错误'));
        storyContents.value = [];
    })
    .finally(() => {
        loadingCount.value--;
    })
}

const userPromptClearCount = ref(0);
const generateStory = userPrompt => {
    if (!storyId.value) {
        alertError('请先选择一个故事、或者新建一个故事');
        return;
    }
    loadingCount.value++;
    request('/story/generate', {
        storyId: storyId.value,
        userPrompt,
        chatSessionId: chatSessionInfo.value.id,
    })
    .then((data) => {
        storyContents.value.push(...data.storyContents);
        chatSessionInfo.value = data.chatSessionInfo;
        userPromptClearCount.value++;
    })
    .catch((error) => {
        alertError('生成故事失败: ' + (typeof error === 'string' ? error : '未知错误'));
    })
    .finally(() => {
        loadingCount.value--;
    })
}

const handleEditModelStoryContent = (args) => {
    const { recordId, contentInx, originalContent } = args;
    ElMessageBox.prompt('编辑情节', '编辑', {
        inputValue: originalContent,
        cancelButtonText: '取消',
        confirmButtonText: '更新情节',
        inputType: 'textarea',
        inputPattern: /^[^\n]+$/,
        inputErrorMessage: '不支持换行符',
    })
    .then(({ value }) => {
        loadingCount.value++;
        return request('/story/edit-model-story-content', {
            chatRecordId: recordId,
            itemInx: contentInx,
            newItemContent: value,
        })
        .then((data) => {
            const newContents = data.newContents;
            for (let i = 0; i < storyContents.value.length; i++) {
                if (storyContents.value[i].id === recordId) {
                    storyContents.value[i].content = newContents;
                    break;
                }
            }
        })
        .finally(() => {
            loadingCount.value--;
        })
    })
    .catch((error) => {
        if (error === 'cancel') {
            return;
        }
        alertError('编辑情节失败: ' + (typeof error === 'string' ? error : '未知错误'));
    });
}

const handleDeleteModelStoryContent = args => {
    const { recordId, contentInx } = args;
    ElMessageBox.confirm('确定删除这个情节吗？', '删除', {
        confirmButtonText: '删除',
        cancelButtonText: '取消',
        type: 'warning',
    })
    .then(() => {
        loadingCount.value++;
        return request('/story/delete-model-story-content', {
            chatRecordId: recordId,
            itemInx: contentInx,
        })
        .then((data) => {
            const newContents = data.newContents;
            for (let i = 0; i < storyContents.value.length; i++) {
                if (storyContents.value[i].id === recordId) {
                    storyContents.value[i].content = newContents;
                    break;
                }
            }
        })
        .finally(() => {
            loadingCount.value--;
        })
    })
    .catch((error) => {
        if (error === 'cancel') {
            return;
        }
        alertError('删除情节失败: ' + (typeof error === 'string' ? error : '未知错误'));
    });
}

</script>

<style>
body, html {
    margin: 0;
    padding: 0;
    width: 100%;
    height: 100%;
    background-color: #F2F3F5;
}
#app {
    width: 100%;
    height: 100%;
}
</style>

<style scoped>
.app {
    width: 100%;
    height: 100%;
    display: flex;
    flex-direction: column;
    align-items: stretch;
}
.header {
    flex-grow: 0; 
    flex-shrink: 0;
    position: relative;
}
.content {
    min-height: 0;
    flex-grow: 1;
    flex-shrink: 1;
    position: relative;
    display: flex;
    align-items: stretch;
}
.story-generate-area-container {
    flex-grow: 1;
    display: flex;
    justify-content: center;
    align-items: stretch;
}
</style>