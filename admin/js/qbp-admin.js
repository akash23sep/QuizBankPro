document.addEventListener('DOMContentLoaded', function() {
	const container = document.getElementById('qbp-rows-container');
	const addBtn = document.getElementById('qbp-add-row');
	const fileInput = document.getElementById('question_image');
	const previewContainer = document.getElementById('qbp-preview-container');
	const imagePreview = document.getElementById('qbp-image-preview');
	const removeImageCheckbox = document.getElementById('remove_question_image');
	const quizFileInput = document.getElementById('quiz_image');
	const quizPreviewContainer = document.getElementById('qbp-quiz-preview-container');
	const quizImagePreview = document.getElementById('qbp-quiz-image-preview');
	const removeQuizImageCheckbox = document.getElementById('remove_quiz_image');

	function restoreQuestionPreview() {
		if (!previewContainer || !imagePreview) {
			return;
		}

		const originalSrc = imagePreview.dataset.originalSrc || '';
		if (originalSrc && !(removeImageCheckbox && removeImageCheckbox.checked)) {
			imagePreview.src = originalSrc;
			previewContainer.style.display = 'block';
		} else {
			imagePreview.src = '#';
			previewContainer.style.display = 'none';
		}
	}

	function restoreQuizPreview() {
		if (!quizPreviewContainer || !quizImagePreview) {
			return;
		}

		const originalSrc = quizImagePreview.dataset.originalSrc || '';
		if (originalSrc && !(removeQuizImageCheckbox && removeQuizImageCheckbox.checked)) {
			quizImagePreview.src = originalSrc;
			quizPreviewContainer.style.display = 'block';
		} else {
			quizImagePreview.src = '#';
			quizPreviewContainer.style.display = 'none';
		}
	}
	
	if (fileInput && previewContainer && imagePreview) {
		fileInput.addEventListener('change', function() {
			const file = this.files[0];
			if (file) {
				const reader = new FileReader();
				reader.onload = function(e) {
					imagePreview.src = e.target.result;
					previewContainer.style.display = 'block';
					if (removeImageCheckbox) {
						removeImageCheckbox.checked = false;
					}
				}
				reader.readAsDataURL(file);
			} else {
				restoreQuestionPreview();
			}
		});

		restoreQuestionPreview();
	}

	if (removeImageCheckbox) {
		removeImageCheckbox.addEventListener('change', function() {
			if (!fileInput || !fileInput.files || !fileInput.files.length) {
				restoreQuestionPreview();
			}
		});
	}

	if (quizFileInput && quizPreviewContainer && quizImagePreview) {
		quizFileInput.addEventListener('change', function() {
			const file = this.files[0];
			if (file) {
				const reader = new FileReader();
				reader.onload = function(e) {
					quizImagePreview.src = e.target.result;
					quizPreviewContainer.style.display = 'block';
					if (removeQuizImageCheckbox) {
						removeQuizImageCheckbox.checked = false;
					}
				}
				reader.readAsDataURL(file);
			} else {
				restoreQuizPreview();
			}
		});

		restoreQuizPreview();
	}

	if (removeQuizImageCheckbox) {
		removeQuizImageCheckbox.addEventListener('change', function() {
			if (!quizFileInput || !quizFileInput.files || !quizFileInput.files.length) {
				restoreQuizPreview();
			}
		});
	}

	const questionsTable = document.getElementById('qbp-questions-table');
	const questionsTableBody = document.getElementById('qbp-questions-table-body');
	const quizzesTable = document.getElementById('qbp-quizzes-table');
	const quizzesTableBody = document.getElementById('qbp-quizzes-table-body');

	function initSortableTable(table, tableBody, options) {
		if (!table || !tableBody || !options || !window.qbpAdmin || !qbpAdmin.ajaxUrl || !options.nonce) {
			return;
		}

		const sortButtons = table.querySelectorAll('.qbp-sort-button');
		if (!sortButtons.length) {
			return;
		}

		function updateSortHeaders(activeColumn, activeOrder) {
			sortButtons.forEach(button => {
				const buttonColumn = button.dataset.sortColumn;
				const isActive = buttonColumn === activeColumn;
				const arrow = button.querySelector('.qbp-sort-arrow');
				const nextOrder = isActive && activeOrder === 'asc' ? 'desc' : 'asc';

				button.classList.toggle('is-active', isActive);
				button.dataset.sortNextOrder = nextOrder;
				button.setAttribute('aria-sort', isActive ? (activeOrder === 'desc' ? 'descending' : 'ascending') : 'none');

				if (arrow) {
					arrow.textContent = isActive ? (activeOrder === 'desc' ? '\u2191' : '\u2193') : '';
				}
			});

			table.dataset.currentOrderby = activeColumn;
			table.dataset.currentOrder = activeOrder;
		}

		function setSortLoading(isLoading) {
			table.classList.toggle('is-loading', isLoading);
			sortButtons.forEach(button => {
				button.disabled = isLoading;
			});
		}

		sortButtons.forEach(button => {
			button.addEventListener('click', function() {
				const orderby = this.dataset.sortColumn || 'id';
				const order = this.dataset.sortNextOrder || 'asc';
				const requestData = new URLSearchParams();

				requestData.append('action', options.action);
				requestData.append('nonce', options.nonce);
				requestData.append('orderby', orderby);
				requestData.append('order', order);

				setSortLoading(true);

				fetch(qbpAdmin.ajaxUrl, {
					method: 'POST',
					credentials: 'same-origin',
					headers: {
						'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8'
					},
					body: requestData.toString()
				})
					.then(response => response.json())
					.then(response => {
						if (!response || !response.success || !response.data || typeof response.data.rows !== 'string') {
							throw new Error('Invalid sort response');
						}

						tableBody.innerHTML = response.data.rows;
						updateSortHeaders(response.data.orderby || orderby, response.data.order || order);
					})
					.catch(() => {
						if (options.errorText) {
							window.alert(options.errorText);
						}
					})
					.finally(() => {
						setSortLoading(false);
					});
			});
		});
	}

	initSortableTable(questionsTable, questionsTableBody, {
		action: 'qbp_sort_questions',
		nonce: window.qbpAdmin ? qbpAdmin.sortQuestionsNonce : '',
		errorText: window.qbpAdmin ? qbpAdmin.sortQuestionsErrorText : ''
	});

	initSortableTable(quizzesTable, quizzesTableBody, {
		action: 'qbp_sort_quizzes',
		nonce: window.qbpAdmin ? qbpAdmin.sortQuizzesNonce : '',
		errorText: window.qbpAdmin ? qbpAdmin.sortQuizzesErrorText : ''
	});

	if (!container || !addBtn) {
		return;
	}

	let defaultChoices = ["", "", "", ""];
	let dragSrcEl = null;
	let initialAnswers = [];
	let initialCorrect = [];

	try {
		initialAnswers = JSON.parse(container.dataset.answers || '[]');
		if (!Array.isArray(initialAnswers)) {
			initialAnswers = [];
		}
	} catch (error) {
		initialAnswers = [];
	}

	try {
		initialCorrect = JSON.parse(container.dataset.correct || '[]').map(String);
		if (!Array.isArray(initialCorrect)) {
			initialCorrect = [];
		}
	} catch (error) {
		initialCorrect = [];
	}

	function syncIndices() {
		const rows = container.querySelectorAll('.qbp-answer-row');
		rows.forEach((row, idx) => {
			const checkbox = row.querySelector('input[type="checkbox"]');
			checkbox.value = idx;
		});
	}

	function handleDragStart(e) {
		dragSrcEl = this;
		e.dataTransfer.effectAllowed = 'move';
		this.style.opacity = '0.4';
	}

	function handleDragOver(e) {
		if (e.preventDefault) { e.preventDefault(); }
		e.dataTransfer.dropEffect = 'move';
		return false;
	}

	function handleDragEnter(e) {
		this.classList.add('over');
		this.style.border = '2px dashed #2271b1';
	}

	function handleDragLeave(e) {
		this.classList.remove('over');
		this.style.border = '1px solid #ddd';
	}

	function handleDrop(e) {
		if (e.stopPropagation) { e.stopPropagation(); }
		if (dragSrcEl !== this) {
			const allRows = Array.from(container.children);
			const srcIdx = allRows.indexOf(dragSrcEl);
			const targetIdx = allRows.indexOf(this);
			if (srcIdx < targetIdx) {
				container.insertBefore(dragSrcEl, this.nextSibling);
			} else {
				container.insertBefore(dragSrcEl, this);
			}
		}
		return false;
	}

	function handleDragEnd(e) {
		this.style.opacity = '1.0';
		const rows = container.querySelectorAll('.qbp-answer-row');
		rows.forEach(row => {
			row.classList.remove('over');
			row.style.border = '1px solid #ddd';
		});
		syncIndices();
	}

	function createRow(textValue = "", isCorrect = false) {
		const rowIndex = container.querySelectorAll('.qbp-answer-row').length;
		const div = document.createElement('div');
		div.className = 'qbp-answer-row';
		div.draggable = true;
		div.style.display = 'flex';
		div.style.alignItems = 'center';
		div.style.gap = '8px';
		div.style.marginBottom = '8px';
		div.style.background = '#fff';
		div.style.padding = '8px';
		div.style.border = '1px solid #ddd';
		div.style.borderRadius = '3px';
		div.style.cursor = 'grab';

		div.innerHTML = `
			<span class="dashicons dashicons-menu" style="color:#2271b1; font-size:18px; width:18px; height:18px;" title="Drag handle"></span>
			<input type="checkbox" name="correct_answers[]" value="${rowIndex}" title="Check if Correct" style="margin:0;" ${isCorrect ? 'checked' : ''}>
			<input type="text" name="answers[]" value="${textValue}" placeholder="Type answer choice option..." style="flex-grow:1; margin:0;" required>
			<button type="button" class="qbp-remove-row" style="background:none; border:none; color:#bc0b0b; cursor:pointer; font-weight:bold; font-size:20px; padding:0 4px; margin:0; line-height:1;" title="Delete Option">×</button>
		`;

		div.addEventListener('dragstart', handleDragStart, false);
		div.addEventListener('dragenter', handleDragEnter, false);
		div.addEventListener('dragover', handleDragOver, false);
		div.addEventListener('dragleave', handleDragLeave, false);
		div.addEventListener('drop', handleDrop, false);
		div.addEventListener('dragend', handleDragEnd, false);

		div.querySelector('.qbp-remove-row').addEventListener('click', function() {
			div.remove();
			syncIndices();
		});

		container.appendChild(div);
		syncIndices();
	}

	const seededChoices = initialAnswers.length ? initialAnswers : defaultChoices;
	seededChoices.forEach((txt, idx) => createRow(txt, initialCorrect.indexOf(String(idx)) !== -1));
	addBtn.addEventListener('click', () => createRow(""));
});
