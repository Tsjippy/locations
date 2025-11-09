document.addEventListener('click', function(event) {
	let target = event.target;
    if(target.classList.contains('icon')){
        let parent  = target.closest('.icon-select-wrapper');
        if(target.tagName == 'DIV'){
            target = target.querySelector('img');
        }

        parent.querySelector('.icon-id').value          = target.dataset.id;

        parent.querySelector('.icon-preview').innerHTML = `<img src="${target.src}" class='icon'>`;

        parent.querySelector('.dropbtn').textContent    = "Change Icon";
    }
});