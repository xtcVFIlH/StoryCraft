<template>
    <div class="header">
        <div class="icon-container hoverable">
            <el-icon 
                :size="25"
                @click="emit('enterChatSessionBar')"
            >
                <ChatLineSquare />
            </el-icon>
        </div>
        <div class="title-container hoverable"
            @click="emit('enterChatSessionInfoEditor')"
            v-show="props.chatSessionTitle"
        >
            <p>{{ props.chatSessionTitle }}</p>
            <el-icon>
                <Edit />
            </el-icon>
        </div>
        <div class="icon-container hoverable">
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

const props = defineProps([
    'chatSessionTitle',
])

const emit = defineEmits([
    'enterChatSessionBar',
    'enterStoryInfoEditor',
    'enterChatSessionInfoEditor',
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

.title-container {
    max-width: 70%;
    overflow: hidden;
    display: flex;
    align-items: center;
}
.title-container>p {
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    margin: 0;
}

.hoverable {
    cursor: pointer;
    transition: var(--el-transition-duration-fast);
}
.hoverable:hover {
    color: var(--el-color-primary-dark-2);
}
</style>