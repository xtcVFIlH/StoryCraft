export function getFullImageUrl(imageName) {
    return process.env.VUE_APP_BACKEND_URI + '/uploads/' + imageName;
}