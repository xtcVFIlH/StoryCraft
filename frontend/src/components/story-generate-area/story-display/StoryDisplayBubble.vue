<template>
    <div v-if="isStoryContentValid" class="story-bubble">
        <div v-if="isDialogueContent || isActionContent" class="story-bubble-with-character">
            <div class="avatar-container">
                <el-avatar :icon="UserFilled" :src="characterAvatarSrc" :size="50"></el-avatar> 
            </div>      
            <div class="name-and-content">
                <p class="name">{{ storyContent.character }}</p>
                <div class="content-container">
                    <p class="content">{{ contentForDisplay }}</p>
                    <div class="right-bar">
                        <div 
                            class="icon-container"
                            @click="emit('deleteModelStoryContent')"
                        >
                            <el-icon size="16">
                                <Delete />
                            </el-icon>
                        </div>
                        <div 
                            class="icon-container"
                            @click="emit('editModelStoryContent')"
                        >
                            <el-icon size="16">
                                <Edit />
                            </el-icon>
                        </div>
                    </div>
                </div>
            </div>      
        </div>
        <div v-if="isDescriptionContent" class="story-bubble-description">
            <div class="content-container">
                <p class="content">{{ contentForDisplay }}</p>
                <div class="right-bar">
                    <div 
                        class="icon-container"
                        @click="emit('deleteModelStoryContent')"
                    >
                        <el-icon size="16">
                            <Delete />
                        </el-icon>
                    </div>
                    <div 
                        class="icon-container"
                        @click="emit('editModelStoryContent')"
                    >
                        <el-icon size="16">
                            <Edit />
                        </el-icon>
                    </div>
                </div>
            </div>
        </div>
        <div v-if="isUserContent" class="story-bubble-description user">
            <p class="content">{{ contentForDisplay }}</p>
            <div class="right-bar">
                <div 
                    class="icon-container"
                    @click="emit('deleteUserStoryContent')"
                >
                    <el-icon size="16">
                        <Delete />
                    </el-icon>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { UserFilled, Delete, Edit } from '@element-plus/icons-vue'
import { computed } from 'vue'
import { getFullImageUrl } from '@/utils/image'

const emit = defineEmits([
    'deleteUserStoryContent',
    'editModelStoryContent',
    'deleteModelStoryContent',
])

const validateInfo = obj => {
    // type: 'dialogue' or 'action' or 'description' 模型输出
    // type : 'user' 用户输入
    if (obj.type !== 'dialogue' && obj.type !== 'action' && obj.type !== 'description' && obj.type !== 'user') {
        return false;
    }
    if (obj.type === 'dialogue' || obj.type === 'action') {
        if (!obj.character) {
            return false;
        }
    }
    if (!obj.content) {
        return false;
    }
    return true;
}

const props = defineProps([
    'storyContent',
    'characterInfos',
])

const isStoryContentValid = computed(() => validateInfo(props.storyContent));
const isDialogueContent = computed(() => props.storyContent.type === 'dialogue');
const isActionContent = computed(() => props.storyContent.type === 'action');
const isDescriptionContent = computed(() => props.storyContent.type === 'description');
const isUserContent = computed(() => props.storyContent.type === 'user');
const contentForDisplay = computed(() => {
    if (isDialogueContent.value || isUserContent.value) {
        return props.storyContent.content;
    }
    return '（' + props.storyContent.content + '）';
})
const characterAvatarSrc = computed(() => {
    const characterInfo = props.characterInfos.find(info => info.name == props.storyContent.character);
    if (characterInfo) {
        return getFullImageUrl(characterInfo.avatar);
    }
    return null;
})

</script>

<style scoped>
p {
    margin: 0;
}
.story-bubble {
    box-sizing: border-box;
    width: 100%;
    padding: 0 5px;
}
.story-bubble-with-character {
    display: flex;
    width: 100%;
}
.avatar-container {
    flex-grow: 0;
    flex-shrink: 0;
}
.name-and-content {
    margin-left: 10px;
    flex-grow: 1;
    flex-shrink: 1;
    min-width: 0;
}
p.name {
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    font-size: 14px;
    font-weight: bold;
    margin-bottom: 5px;
}
p.content {
    box-sizing: border-box;
    padding: 20px;
    font-size: 14px;
    background-color: var(--el-color-success-light-5);
    color: #000;
    border-radius: 5px;
    word-break: break-all;
    line-height: 2;
    flex-grow: 1;
    flex-shrink: 1;
}
.story-bubble-description {
    width: 100%;
}
.story-bubble-description.user {
    display: flex;
}
.story-bubble-description.user>p.content {
    background-color: var(--el-color-info-light-5);
}
.right-bar {
    box-sizing: border-box;
    padding: 5px 0 5px 5px;
    display: flex;
    flex-direction: column;
}
.right-bar>*:not(:last-child) {
    margin-bottom: 10px;
}
.icon-container {
    cursor: pointer;
    transition: var(--el-transition-duration-fast);
}
.icon-container:hover {
    color: var(--el-color-primary-dark-2);
}
.content-container {
    display: flex;
    align-items: stretch;
    width: 100%;
}
</style>