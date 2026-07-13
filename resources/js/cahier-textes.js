window.cahierTexte = function (programId, date) {
    return {
        programId,
        date,
        toggleChapter(chapterId) {
            fetch('/cahier-textes/toggle', {
                method: 'POST',
                headers: {'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content},
                body: JSON.stringify({ chapter_id: chapterId, date: this.date })
            }).then(response => response.json()).then(() => {
                this.$dispatch('saved');
            });
        },
        saveRemark(chapterId, value) {
            fetch(`/cahier-textes/${chapterId}/remark`, {
                method: 'PATCH',
                headers: {'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content},
                body: JSON.stringify({ remarque: value })
            });
        }
    };
};
