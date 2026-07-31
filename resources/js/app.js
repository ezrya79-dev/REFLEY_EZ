import Alpine from 'alpinejs';
import { createAvatarEditor } from './avatar-editor.js';

// Composants Alpine partagés (déclarés avant Alpine.start()).
Alpine.data('avatarEditor', createAvatarEditor);

window.Alpine = Alpine;
Alpine.start();
