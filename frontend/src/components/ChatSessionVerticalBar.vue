<template>
    <transition name="el-fade-in">
        <div class="layer" 
            @click="emit('update:modelValue', false)" 
            v-show="modelValue"
        ></div>
    </transition>   
    <transition name="slide-in">
        <div 
            class="vertical-container"
            v-show="modelValue"
            v-loading="isLoading"
        >
            <div 
                class="chat-session-bar"
                v-show="chatSessions.length > 0"
            >
                <div
                    class="chat-session-container"
                >
                    <div 
                        class="chat-session"
                        @click="handleClickChatsessionNewBtn"
                    >
                        开启新会话
                    </div>
                </div>
                <div 
                    class="chat-session-container"
                    v-for="session in chatSessions" 
                    :key="session.id" 
                >
                    <div 
                        class="chat-session" 
                        :class="{ selected: session.id === chatSessionId }"
                        @click="handleClickChatSession(session.id)"
                    >
                        {{ session.title }}
                    </div>
                    <div 
                        class="icon-container chat-session-delete"
                        @click="handleClickChatSessionDeleteBtn(session.id)"
                    >
                        <el-icon 
                        >
                            <Delete />
                        </el-icon>
                    </div>
                </div>
            </div>
            <div 
                class="refresh-btn-container"
                v-show="chatSessions.length === 0"
            >
                <div 
                    class="icon-container refresh"
                    @click="handleRefreshBtnClick"
                >
                    <el-icon >
                        <RefreshLeft />
                    </el-icon>
                </div>
            </div>
        </div>
    </transition>
</template>

<script setup>
import axios from 'axios';
import { useRequest } from '@/composables/useRequest';
import { watch, computed, ref } from 'vue';
import { ElNotification, ElMessageBox } from 'element-plus'
import { RefreshLeft, Delete } from '@element-plus/icons-vue'
import { useLocalStorage } from '@/composables/useLocalStorage';

const { request } = useRequest();

const alertError = error => {
    ElNotification({
        title: '错误',
        message: error,
        type: 'error',
    });
}
const closeBar = () => {
    emit('update:modelValue', false);
}

const props = defineProps([
    'storyId',
    'chatSessionId',
    'chatSessionTitle',
    'modelValue', // 绑定侧边栏的显示状态
]);
const emit = defineEmits([
    'updateChatSessionInfo',
    'update:modelValue',
    'sessionsLoadingStart',
    'sessionsLoadingEnd',
    'createNewChatSession',
]);

const chatSessions = ref([]);
const recentlySelectedChatSessionIdTable = useLocalStorage('recentlySelectedChatSessionIdTable', {});
const chatSessionId = ref(null);
const chatSessionTitle = computed(() => {
    return chatSessions.value.find(session => session.id === chatSessionId.value)?.title || '';
})
const handleClickChatSession = id => {
    chatSessionId.value = id;
    closeBar();
}
watch(() => chatSessionId.value, (newValue) => {
    if (props.storyId) {
        recentlySelectedChatSessionIdTable.value[props.storyId] = newValue;
    }
    emit('updateChatSessionInfo', {
        id: newValue,
        title: chatSessionTitle.value,
    });
})
watch(() => props.chatSessionId, (newValue) => {
    (newValue ? refreshChatSessions() : Promise.resolve())
    .then(() => {
        chatSessionId.value = newValue;
    });
})
watch(() => props.chatSessionTitle, (newValue) => {
    for (let i = 0; i < chatSessions.value.length; i++) {
        if (chatSessions.value[i].id === chatSessionId.value) {
            chatSessions.value[i].title = newValue;
            break;
        }
    }
})

let cancelTokenSource = null;
const isLoading = computed(() => {
    return cancelTokenSource !== null;
})
const refreshChatSessions = () => {
    if (!props.storyId) {
        chatSessions.value = [];
        return Promise.resolve();
    }

    if (cancelTokenSource) {
        cancelTokenSource.cancel();
    }
    cancelTokenSource = axios.CancelToken.source();

    emit('sessionsLoadingStart');

    return request('/chat-session/get-all', {
        storyId: props.storyId,
    }, {}, cancelTokenSource.token)
    .then(data => {
        chatSessions.value = data.chatSessions;
    })
    .catch(error => {
        if (!axios.isCancel(error)) {
            alertError('获取会话列表失败: ' + (typeof error === 'string' ? error : error.message));
        }
    })
    .finally(() => {
        cancelTokenSource = null;
        emit('sessionsLoadingEnd');
    });
}
watch(() => props.storyId, () => {
    refreshChatSessions()
    .then(() => {
        if (props.storyId) {
            chatSessionId.value = recentlySelectedChatSessionIdTable.value[props.storyId] || null;
        }
        else {
            chatSessionId.value = null;
        }
    });
})
const handleRefreshBtnClick = () => {
    chatSessionId.value = null;
    refreshChatSessions();
}
const handleClickChatsessionNewBtn = () => {
    chatSessionId.value = null;
    emit('createNewChatSession');
    closeBar();
}

const handleClickChatSessionDeleteBtn = deleteChatSessionId => {
    ElMessageBox.confirm('确定删除该会话吗？', '删除', {
        confirmButtonText: '删除',
        cancelButtonText: '取消',
        type: 'warning',
    })
    .then(() => {
        return request('/chat-session/delete', {
            chatSessionId: deleteChatSessionId,
            storyId: props.storyId,
        })
        .then(() => {
            chatSessions.value = chatSessions.value.filter(session => session.id !== deleteChatSessionId);
            if (chatSessionId.value === deleteChatSessionId) {
                chatSessionId.value = null;
            }
        });
    })
    .catch((error) => {
        if (error === 'cancel') {
            return;
        }
        alertError('删除会话失败: ' + (typeof error === 'string' ? error : '未知错误'));
    });

}

</script>

<style scoped>
.layer {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background-color: rgba(0, 0, 0, .5);
}
.vertical-container {
    box-sizing: border-box;
    border-right: 1px solid var(--el-border-color);
    width: 250px;
    position: fixed;
    top: 0;
    bottom: 0;
    left: 0;
    display: flex;
    align-items: stretch;
}
.slide-in-enter-active, .slide-in-leave-active {
    transition: var(--el-transition-duration-fast);
}
.slide-in-enter-from, .slide-in-leave-to {
    transform: translate3d(-100%, 0, 0);
}
.slide-in-enter-to, .slide-in-leave-from {
    transform: translate3d(0, 0, 0);
}
.chat-session-bar {
    box-sizing: border-box;
    padding: 10px;
    background-color: #fff;
    width: 100%;
    overflow-y: auto;
}
.chat-session-bar>*:not(:last-child) {
    margin-bottom: 10px;
}
.refresh-btn-container {
    background-color: #fff;
    width: 100%;
    display: flex;
    justify-content: center;
    align-items: center;
}
.icon-container {
    box-sizing: border-box;
    cursor: pointer;
    transition: var(--el-transition-duration-fast);
}
.icon-container.refresh {
    font-size: 30px;
}
.icon-container.chat-session-delete {
    margin-left: 10px;
}
.icon-container:hover {
    color: var(--el-color-primary-light-3);
}
.chat-session-container {
    display: flex;
    align-items: center;
}
.chat-session {
    font-size: 14px;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
    box-sizing: border-box;
    padding: 10px;
    border-radius: 6px;
    transition: var(--el-transition-duration-fast);
    color: #000;
    flex-grow: 1;
    flex-shrink: 1;
}
.chat-session:hover {
    cursor: pointer;
    color: var(--el-color-primary-light-3);
}
.chat-session.selected {
    font-weight: bold;
    color: var(--el-color-primary-dark-2);
    background-color: var(--el-color-primary-light-8);
}
</style>