export default function photoPositioner(initX, initY, existingUrl) {
    return {
        posX: initX ?? 50,
        posY: initY ?? 50,
        photoUrl: existingUrl ?? null,
        dragging: false,
        startX: 0,
        startY: 0,
        startPosX: 0,
        startPosY: 0,

        onFileChange(event) {
            const file = event.target.files[0];
            this.photoUrl = file ? URL.createObjectURL(file) : this.photoUrl;
            this.posX = 50;
            this.posY = 50;
        },

        clear() {
            if (this.$refs.fileInput) {
                this.$refs.fileInput.value = '';
            }
            this.photoUrl = null;
            this.posX = 50;
            this.posY = 50;
        },

        point(event) {
            return event.touches && event.touches.length ? event.touches[0] : event;
        },

        startDrag(event) {
            if (!this.photoUrl) return;
            event.preventDefault();
            this.dragging = true;
            const p = this.point(event);
            this.startX = p.clientX;
            this.startY = p.clientY;
            this.startPosX = this.posX;
            this.startPosY = this.posY;
        },

        onDrag(event) {
            if (!this.dragging) return;
            const p = this.point(event);
            const rect = this.$refs.box.getBoundingClientRect();
            const dx = p.clientX - this.startX;
            const dy = p.clientY - this.startY;
            this.posX = Math.min(100, Math.max(0, this.startPosX - (dx / rect.width) * 100));
            this.posY = Math.min(100, Math.max(0, this.startPosY - (dy / rect.height) * 100));
        },

        endDrag() {
            this.dragging = false;
        },

        reset() {
            this.posX = 50;
            this.posY = 50;
        },
    };
}
