document.addEventListener('DOMContentLoaded', () => {
    const kanbanCardsContainers = document.querySelectorAll('.kanban-cards');
    const kanbanCards = document.querySelectorAll('.kanban-card');

    let draggedCard = null;

    // Add event listeners for draggable cards
    kanbanCards.forEach(card => {
        card.addEventListener('dragstart', (e) => {
            draggedCard = card;
            setTimeout(() => {
                card.classList.add('dragging');
            }, 0);
        });

        card.addEventListener('dragend', () => {
            draggedCard.classList.remove('dragging');
            draggedCard = null;
        });
    });

    // Add event listeners for kanban card containers (columns)
    kanbanCardsContainers.forEach(container => {
        container.addEventListener('dragover', (e) => {
            e.preventDefault(); // Allow drop
            const afterElement = getDragAfterElement(container, e.clientY);
            const dragging = document.querySelector('.dragging');
            if (dragging) {
                if (afterElement == null) {
                    container.appendChild(dragging);
                } else {
                    container.insertBefore(dragging, afterElement);
                }
            }
            container.classList.add('drag-over');
        });

        container.addEventListener('dragleave', () => {
            container.classList.remove('drag-over');
        });

        container.addEventListener('drop', (e) => {
            e.preventDefault();
            container.classList.remove('drag-over');
            if (draggedCard) {
                // The dragover event already handled the appending/inserting
                // Here you might update the backend if this were a real application
                console.log(`Card ${draggedCard.id} dropped into column ${container.dataset.status}`);
            }
        });
    });

    function getDragAfterElement(container, y) {
        const draggableElements = [...container.querySelectorAll('.kanban-card:not(.dragging)')];

        return draggableElements.reduce((closest, child) => {
            const box = child.getBoundingClientRect();
            const offset = y - box.top - box.height / 2;
            if (offset < 0 && offset > closest.offset) {
                return { offset: offset, element: child };
            } else {
                return closest;
            }
        }, { offset: Number.NEGATIVE_INFINITY }).element;
    }
});
