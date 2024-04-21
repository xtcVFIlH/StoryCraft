<template>
    <div class="container">
        <character-info-editor-card
            v-for="(characterInfo, index) in localCharacterInfos"
            :key="characterInfo.__temp_id"
            :character-info="localCharacterInfos[index]"
            @update="handleUpdateCharacterInfo(index, $event)"
            @delete="handleDeleteCharacterInfo(index)"
        ></character-info-editor-card>
        <div
            class="add-card"
            @click="handleAddCharacter"
        >
            <span>+</span>
        </div>
    </div>
</template>

<script setup>
import CharacterInfoEditorCard from './character-info-editor/CharacterInfoEditorCard'
import { debounce, isEqual } from 'lodash'
import { watch, ref } from 'vue'

const characterDefaultInfo = {
    name: '',
    feature: '',
    avatar: null,
}

const props = defineProps([
    'characterInfos',
])
const emit = defineEmits([
    'updateCharacterInfos',
])

const localizeCharacterInfos = infos => {
    return infos.map(info => {
        return {
            __temp_id: Math.random() * 1e9 | 0,
            ...info
        }
    })
}
const delocalizeCharacterInfos = infos => {
    return infos.map(info => {
        // eslint-disable-next-line no-unused-vars
        const { __temp_id, ...rest } = info;
        return rest;
    })
}

const localCharacterInfos = ref(localizeCharacterInfos(props.characterInfos))

const handleAddCharacter = () => {
    localCharacterInfos.value.push({ 
        __temp_id: Math.random() * 1e9 | 0,
        ...characterDefaultInfo 
    });
}
const handleUpdateCharacterInfo = (index, newValue) => {
    localCharacterInfos.value[index] = JSON.parse(JSON.stringify(newValue));
}
const handleDeleteCharacterInfo = index => {
    localCharacterInfos.value.splice(index, 1);
}

const debouncedUpdatecharacterInfos = debounce(() => {
    emit('updateCharacterInfos', delocalizeCharacterInfos(localCharacterInfos.value))
}, 300)
watch(() => localCharacterInfos, () => {
    debouncedUpdatecharacterInfos()
}, { deep: true })
watch(() => props.characterInfos, (newValue) => {
    if (isEqual(newValue, delocalizeCharacterInfos(localCharacterInfos.value))) {
        return;
    }
    localCharacterInfos.value = JSON.parse(JSON.stringify(localizeCharacterInfos(newValue)));
}, { immediate: true })
</script>

<style scoped>
.container {
    display: flex;
    align-items: center;
    box-sizing: border-box;
    padding: 10px;
    border-radius: 5px;
    background-color:#fff;
    border: 1px solid var(--el-border-color);
    overflow-x: auto;
}
.container>*:not(:last-child) {
    margin-right: 10px;
}
.container>* {
    flex-shrink: 0;
}
.add-card {
    box-sizing: border-box;
    border: 1px dashed var(--el-border-color);
    border-radius: 5px;
    width: 225px;
    height: 300px;
    display: flex;
    justify-content: center;
    align-items: center;
    color: #c0c4cc;
    transition: var(--el-transition-duration-fast);
}
.add-card:hover {
    cursor: pointer;
    border-color: var(--el-color-primary);
    color: var(--el-color-primary);
}
</style>