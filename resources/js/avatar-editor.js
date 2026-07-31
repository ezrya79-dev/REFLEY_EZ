/**
 * Éditeur d'avatar côté client : aperçu, zoom et recadrage sur canvas avant
 * envoi. Le serveur revalide et recadre de toute façon — ceci n'est qu'un
 * confort d'édition. La logique de calcul (clampOffset, coverScale) est pure
 * pour être testable sous Vitest sans navigateur.
 */

export const EDITOR_SIZE = 256;

/** Échelle minimale pour que l'image couvre entièrement le cadre carré. */
export function coverScale(imageWidth, imageHeight, frame = EDITOR_SIZE) {
    if (imageWidth <= 0 || imageHeight <= 0) {
        return 1;
    }

    return Math.max(frame / imageWidth, frame / imageHeight);
}

/** Borne le déplacement pour que l'image ne laisse jamais de vide. */
export function clampOffset(offset, imageSize, scale, frame = EDITOR_SIZE) {
    const scaled = imageSize * scale;
    const min = frame - scaled;

    return Math.min(0, Math.max(min, offset));
}

export function createAvatarEditor() {
    return {
        image: null,
        zoom: 1,
        offsetX: 0,
        offsetY: 0,
        dragging: false,
        lastX: 0,
        lastY: 0,
        fileName: '',

        pick(event) {
            const file = event.target.files[0];

            if (!file) {
                return;
            }

            this.fileName = file.name;
            const url = URL.createObjectURL(file);
            const img = new Image();
            img.onload = () => {
                this.image = img;
                this.zoom = 1;
                this.offsetX = 0;
                this.offsetY = 0;
                this.render();
                URL.revokeObjectURL(url);
            };
            img.src = url;
        },

        baseScale() {
            return this.image ? coverScale(this.image.width, this.image.height) : 1;
        },

        render() {
            const canvas = this.$refs.canvas;

            if (!canvas || !this.image) {
                return;
            }

            const ctx = canvas.getContext('2d');
            const scale = this.baseScale() * this.zoom;
            this.offsetX = clampOffset(this.offsetX, this.image.width, scale);
            this.offsetY = clampOffset(this.offsetY, this.image.height, scale);

            ctx.clearRect(0, 0, EDITOR_SIZE, EDITOR_SIZE);
            ctx.drawImage(
                this.image,
                this.offsetX,
                this.offsetY,
                this.image.width * scale,
                this.image.height * scale,
            );
        },

        startDrag(event) {
            this.dragging = true;
            this.lastX = event.clientX;
            this.lastY = event.clientY;
        },

        drag(event) {
            if (!this.dragging) {
                return;
            }

            this.offsetX += event.clientX - this.lastX;
            this.offsetY += event.clientY - this.lastY;
            this.lastX = event.clientX;
            this.lastY = event.clientY;
            this.render();
        },

        endDrag() {
            this.dragging = false;
        },

        setZoom(value) {
            this.zoom = Number(value);
            this.render();
        },

        /** Remplace le fichier du champ par le rendu recadré du canvas. */
        async apply(input) {
            if (!this.image) {
                return;
            }

            const canvas = this.$refs.canvas;
            const blob = await new Promise((resolve) => canvas.toBlob(resolve, 'image/png'));
            const file = new File([blob], 'avatar.png', { type: 'image/png' });
            const transfer = new DataTransfer();
            transfer.items.add(file);
            input.files = transfer.files;
        },
    };
}
