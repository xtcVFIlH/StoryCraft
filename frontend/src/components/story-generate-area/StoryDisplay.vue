<template>
    <div class="story-display">
        <div v-for="(contents) in props.storyContents"
            :key="contents.id"
        >
            <story-display-bubble
                v-for="(content, index) in contents.content"
                :key="index"
                :story-content="content"
                :character-infos="props.characterInfos"
                @delete-user-story-content="emit('deleteUserStoryContent', contents.id)"
                @edit-model-story-content="emit('editModelStoryContent', { recordId: contents.id, contentInx: index, originalContent: content.content })"
                @delete-model-story-content="emit('deleteModelStoryContent', { recordId: contents.id, contentInx: index })"
            ></story-display-bubble>
        </div>
    </div>
</template>
  
<script setup>
import StoryDisplayBubble from './story-display/StoryDisplayBubble'

const emit = defineEmits([
    'deleteUserStoryContent',
    'editModelStoryContent',
    'deleteModelStoryContent',
])

const props = defineProps([
    'storyContents',
    'characterInfos',
])
</script>

<style scoped>
</style>