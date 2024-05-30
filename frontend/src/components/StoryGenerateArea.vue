<template>
    <div class="story-generate-area">
        <div class="story-display-container">
            <StoryDisplay
                :story-contents="props.storyContents"
                :character-infos="props.characterInfos"
                @delete-user-story-content="emit('deleteUserStoryContent', $event)"
                @edit-model-story-content="emit('editModelStoryContent', $event)"
                @delete-model-story-content="emit('deleteModelStoryContent', $event)"
            ></StoryDisplay>
        </div>
        <div class="prompt-editor-container">
            <PromptEditor
                :userPromptClearCount="props.userPromptClearCount"
                @submit="emit('submitUserPrompt', $event)"
            ></PromptEditor>
        </div>
    </div>
</template>

<script setup>

import PromptEditor from './story-generate-area/PromptEditor.vue'
import StoryDisplay from './story-generate-area/StoryDisplay.vue'

const props = defineProps([
    'storyContents',
    'characterInfos',
    'userPromptClearCount',
])

const emit = defineEmits([
    'submitUserPrompt',
    'deleteUserStoryContent',
    'editModelStoryContent',
    'deleteModelStoryContent',
])

</script>

<style scoped>
.story-generate-area {
    max-width: 768px;
    width: 100%;
    box-sizing: border-box;
    display: flex;
    flex-direction: column;
    align-items: stretch;
}
.story-display-container {
    min-height: 0;
    flex-grow: 1;
    flex-shrink: 1;
    overflow: auto;
}
.prompt-editor-container {
    flex-grow: 0;
    flex-shrink: 0;
}
</style>