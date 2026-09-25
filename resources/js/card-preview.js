import Alpine from 'alpinejs';

Alpine.store('cardEdit', {
    editMode: false,
    saving: false,
    saved: false,
});

window.cardPhotoPositions = {};

window.cardPhotoDrag = function (kind, id, initX, initY) {
    const key = kind + '-' + (id ?? '0');

    return {
        posX: initX,
        posY: initY,
        dragging: false,
        startX: 0,
        startY: 0,
        startPosX: 0,
        startPosY: 0,

        init() {
            window.cardPhotoPositions[key] = { kind, id, x: Math.round(this.posX), y: Math.round(this.posY) };
        },

        point(event) {
            return event.touches && event.touches.length ? event.touches[0] : event;
        },

        startDrag(event) {
            if (!Alpine.store('cardEdit').editMode) return;
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
            const rect = this.$el.getBoundingClientRect();
            const dx = p.clientX - this.startX;
            const dy = p.clientY - this.startY;
            this.posX = Math.min(100, Math.max(0, this.startPosX - (dx / rect.width) * 100));
            this.posY = Math.min(100, Math.max(0, this.startPosY - (dy / rect.height) * 100));
            window.cardPhotoPositions[key].x = Math.round(this.posX);
            window.cardPhotoPositions[key].y = Math.round(this.posY);
        },

        endDrag() {
            this.dragging = false;
        },
    };
};

window.saveCardPhotoPositions = function (studentId, url) {
    const store = Alpine.store('cardEdit');
    store.saving = true;
    store.saved = false;

    const student = window.cardPhotoPositions['student-0'];
    const guardians = Object.values(window.cardPhotoPositions)
        .filter((p) => p.kind === 'guardian')
        .map((p) => ({ id: p.id, photo_position_x: p.x, photo_position_y: p.y }));

    fetch(url, {
        method: 'PATCH',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            Accept: 'application/json',
        },
        body: JSON.stringify({
            photo_position_x: student ? student.x : 50,
            photo_position_y: student ? student.y : 50,
            guardians,
        }),
    })
        .then((r) => {
            if (!r.ok) throw new Error('save failed');
            return r.json();
        })
        .then(() => {
            store.saving = false;
            store.saved = true;
            setTimeout(() => (store.saved = false), 2000);
        })
        .catch(() => {
            store.saving = false;
            alert("Une erreur est survenue pendant l'enregistrement.");
        });
};

window.Alpine = Alpine;
Alpine.start();
