<template>
    <div class="header">
        <div class="icon-container">
            <el-icon 
                :size="25"
                @click="emit('enterChatSessionBar')"
            >
                <ChatLineSquare />
            </el-icon>
        </div>
        <p>{{ props.chatSessionTitle }}</p>
        <div class="icon-container">
            <el-icon
                :size="25"
                @click="emit('enterStoryInfoEditor')"
            >
                <SwitchFilled />
            </el-icon>
        </div>
    </div>
</template>

<script setup>

import { ChatLineSquare, SwitchFilled } from '@element-plus/icons-vue'

const props = defineProps([
    'chatSessionTitle',
])

const emit = defineEmits([
    'enterChatSessionBar',
    'enterStoryInfoEditor',
])

</script>

<style scoped>
.header {
    width: 100%;
    box-sizing: border-box;
    padding: 10px 20px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    background-color: var(--el-menu-bg-color);
    border-bottom: 1px solid var(--el-border-color);
}
.header>p {
    margin: 0;
}
.icon-container {
    transition: var(--el-transition-duration-fast);
}
.icon-container:hover {
    cursor: pointer;
    color: var(--el-color-primary-dark-2);
}
</style>