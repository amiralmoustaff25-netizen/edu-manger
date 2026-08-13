window.cahierTexte = function (programId, date, initialCompletions) {
    return {
        programId,
        date,
        completedToday: initialCompletions || {},
        toggleChapter(chapterId) {
            const previous = !!this.completedToday[chapterId];
            this.completedToday[chapterId] = !previous;
            fetch('/cahier-textes/toggle', {
                method: 'POST',
                headers: {'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content},
                body: JSON.stringify({ chapter_id: chapterId, date: this.date })
            }).then(response => response.json()).then((data) => {
                if (data && data.chapter && typeof data.chapter.completed === 'boolean') {
                    this.completedToday[chapterId] = data.chapter.completed;
                }
                this.$dispatch('saved');
            }).catch(() => {
                this.completedToday[chapterId] = previous;
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
