document.addEventListener('DOMContentLoaded', () => {
  const bindStudentSearch = (inputId, resultId, selectName, tableBodySelector = null) => {
    const input = document.getElementById(inputId);
    const result = document.getElementById(resultId);
    if (!input || !result) return;
    const select = selectName ? document.querySelector(`select[name="${selectName}"]`) : null;
    let timer;
    let requestId = 0;

    const filterRows = term => {
      if (!tableBodySelector) return;
      const rows = Array.from(document.querySelectorAll(tableBodySelector));
      let visibleCount = 0;
      rows.forEach(row => {
        row.hidden = !row.textContent.toLocaleLowerCase().includes(term.toLocaleLowerCase());
        if (!row.hidden) visibleCount++;
      });
      const empty = document.querySelector('[data-no-students]');
      if (empty) empty.hidden = term.length === 0 || visibleCount > 0;
    };

    input.addEventListener('input', () => {
      const term = input.value.trim();
      filterRows(term);
      clearTimeout(timer);
      const currentRequest = ++requestId;
      result.replaceChildren();
      result.hidden = true;
      if (term.length < 1 || !select) return;

      timer = setTimeout(async () => {
        try {
          const response = await fetch(`index.php?page=api-students&q=${encodeURIComponent(term)}`, {
            headers: {Accept: 'application/json'},
            credentials: 'same-origin'
          });
          if (!response.ok) throw new Error('Student search failed');
          const students = await response.json();
          if (currentRequest !== requestId) return;
          result.replaceChildren();
          if (!students.length) {
            const empty = document.createElement('p');
            empty.className = 'student-search-empty';
            empty.textContent = 'No matching students.';
            result.appendChild(empty);
          } else {
            const list = document.createElement('ul');
            list.className = 'student-search-list';
            students.forEach(student => {
              const item = document.createElement('li');
              const button = document.createElement('button');
              button.type = 'button';
              button.className = 'student-search-option';
              button.textContent = `${student.full_name} (${student.student_code})`;
              button.addEventListener('click', () => {
                const option = Array.from(select.options).find(candidate => candidate.value === String(student.id));
                if (!option) {
                  result.textContent = 'This student is not available on your assigned bus.';
                  return;
                }
                select.value = option.value;
                select.dispatchEvent(new Event('change', {bubbles: true}));
                input.value = button.textContent;
                result.replaceChildren();
                result.hidden = true;
              });
              item.appendChild(button);
              list.appendChild(item);
            });
            result.appendChild(list);
          }
          result.hidden = false;
        } catch (error) {
          if (currentRequest !== requestId) return;
          const message = document.createElement('p');
          message.className = 'student-search-empty';
          message.textContent = 'Student search could not load. Check your connection and try again.';
          result.replaceChildren(message);
          result.hidden = false;
        }
      }, 180);
    });
  };

  bindStudentSearch('studentSearch', 'searchResults', null, '.student-table tbody tr[data-student-row]');
  bindStudentSearch('attendanceSearch', 'attendanceSearchResults', 'student_id');

  document.addEventListener('submit', event => {
    const form = event.target;
    if (form instanceof HTMLFormElement && form.dataset.confirm && !window.confirm(form.dataset.confirm)) {
      event.preventDefault();
    }
  });
});
