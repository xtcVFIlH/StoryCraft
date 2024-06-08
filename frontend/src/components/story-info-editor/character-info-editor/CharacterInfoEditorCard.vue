<template>
    <div class="card">
        <div
            style="flex-grow: 0; flex-shrink: 0; display: flex; justify-content: center;"
        >
            <avatar-uploader
                v-model="localCharacterInfo.avatar"
            ></avatar-uploader>
        </div>
        <el-input
            v-model="localCharacterInfo.name"
            placeholder="角色名称"
            style="flex-grow: 0; flex-shrink: 0;"
        ></el-input>
        <el-input
            v-model="localCharacterInfo.feature"
            placeholder="角色特征"
            type="textarea"
            show-word-limit
            resize="none"
            class="autosize-textarea"
            style="flex-grow: 1; flex-shrink: 1;"
        ></el-input>
        <div 
            class="delete-button-container" 
            @click="emits('delete')"
        >
            <el-icon size="16">
                <Close />
            </el-icon>
        </div>
    </div>
</template>

<script setup>
import { ref, watch } from 'vue'
import { debounce, isEqual } from 'lodash'
import AvatarUploader from './character-info-editor-card/AvatarUploader'
import { Close } from '@element-plus/icons-vue'

const props = defineProps([
    'characterInfo',
])

const emits = defineEmits([
    'update',
    'delete',
])

const localCharacterInfo = ref({ ...props.characterInfo })

const debouncedUpdateCharacterInfo = debounce(() => {
    emits('update', localCharacterInfo.value);
}, 300)
watch(() => localCharacterInfo, () => {
    debouncedUpdateCharacterInfo();
}, { deep: true })
watch(() => props.characterInfo, (newValue) => {
    if (isEqual(newValue, localCharacterInfo.value)) {
        return;
    }
    localCharacterInfo.value = { ...newValue };
})

</script>

<style scoped>
.card {
    margin: 0;
    background-color: white;
    box-sizing: border-box;
    padding: 10px;
    display: flex;
    flex-direction: column;
    align-items: stretch;
    border-radius: 5px;
    width: 225px;
    height: 300px;
    border-radius: 5px;
    border: 1px solid #ebeef5;
    position: relative;
}
.card>*:not(:last-child) {
    margin-bottom: 10px;
}
.autosize-textarea /deep/ textarea {
    height: 100% !important;
}
.delete-button-container {
    position: absolute;
    cursor: pointer;
    right: 5px;
    top: 5px;
    line-height: 0;
    color: var(--el-color-info);
}
</style>