(function(){
    const activePopupClass = 'active';
    const activeVacancyClass = 'active';
    const activeMenuClass = 'active';
    const selectedItemClass = 'selected';

    function selectElement(element, list) {
        list.forEach(el => { el.classList.remove(selectedItemClass)});
        element.classList.add(selectedItemClass);
    }
    function allowSelection(elements) {
        attachEventsToList(elements, 'click', event => {
            selectElement(event.target, elements);
        });
    }
    function scrollTo(element) {
        window.scrollTo({
            'behavior': 'smooth',
            'left': 0,
            'top': element.offsetTop
        });
    }
    function attachEventsToList(list, eventName, callback) {
        list.forEach(item => {
            item.addEventListener(eventName, event => {
                callback(event, item);
            });
        });
    }

    // Scrolls
    const scrollableLinks = document.querySelectorAll('.scroll-link');
    attachEventsToList(scrollableLinks, 'click', event => {
        event.preventDefault();
        scrollTo(document.querySelector(event.target.getAttribute('data-scroll-id')));
    });

    // Selections
    const platformItems = document.querySelectorAll('.platform-item');
    allowSelection(platformItems);

    const projectTicks = document.querySelectorAll('.project-switcher a');
    allowSelection(projectTicks);

    const projectContainer = document.querySelector('.projects-list');
    if (projectContainer) {
        projectContainer.addEventListener('scroll', () => {
            const selectedIndex = Math.floor(projectContainer.scrollLeft / projectContainer.offsetWidth);
            const projectTick = projectTicks[selectedIndex];
            if (!projectTick.classList.contains(selectedItemClass)) {
                selectElement(projectTick, projectTicks)
            }
        });
    }

    const hash = window.location.hash;
    if (hash) {
        const activeProject = document.querySelector('.project-switcher a[href="' + hash + '"]')
        if (activeProject) {
            selectElement(activeProject, projectTicks)
        } else {
            const activeElement = document.querySelector(hash);
            if (activeElement) {
                scrollTo(activeElement);
            }
        }
    }

    // Menu
    const menuButton = document.querySelector('.mobile-menu');
    function toggleMenu() {
        menu.classList.toggle(activeMenuClass);
    }
    const menu = document.querySelector('.float-menu');
    menuButton.addEventListener('click', toggleMenu);
    const closeMenu = document.querySelector('.close-menu');
    closeMenu.addEventListener('click', toggleMenu);

    const menuButtons = document.querySelectorAll('.menu-body a');
    attachEventsToList(menuButtons, 'click', event => {
        event.preventDefault();
        selectElement(event.target, menuButtons);
        toggleMenu();
        const href = event.target.getAttribute("href");
        if (href.indexOf("#") === 0) {
            setTimeout(() => {
                scrollTo(document.querySelector(event.target.getAttribute("href")))
            }, 250);
        } else {
            window.location.href = href;
        }
    });

    // Popups
    const popupLinks = document.querySelectorAll('.popup-link');
    attachEventsToList(popupLinks, 'click', (event, item) => {
        event.preventDefault();
        const popupSelector = item.getAttribute('data-popup-selector');
        const popup = document.querySelector(popupSelector);
        if (popup) {
            popup.classList.add(activePopupClass);
            popup.focus();
        }
    });

    function hidePopup() {
        const popup = document.querySelector('.popup.' + activePopupClass);
        if (popup) {
            popup.classList.remove(activePopupClass);
        }
    }

    const closeButtons = document.querySelectorAll('.popup-close');
    attachEventsToList(closeButtons, 'click', hidePopup);
    document.body.addEventListener('keyup', event => {
        if (event.code === 'Escape') {
            hidePopup();
        }
    });
    const overlay = document.querySelectorAll('.popup-overflow');
    attachEventsToList(overlay, 'click', hidePopup);

    function loadUrl(url) {
        return new Promise((resolve, reject) => {
            var client = new XMLHttpRequest();
            client.open('GET', url);
            client.onload = () => {
                if (client.status === 200) {
                    resolve(client.responseText);
                } else {
                    reject(client.statusText);
                }
            };
            client.send();
        });
    }

    // Vacancies
    const vacanciesContainer = document.querySelector('.vacancies-container');
    if (vacanciesContainer) {
        function redrawVacancies(vacancies) {
            vacanciesContainer.innerHTML = '';//remove listeners if will load more than once
            vacancies.forEach(vacancy => {
                vacanciesContainer.appendChild( getVacancyItem(vacancy) );
            });
        }
        function getVacancyItem(vacancy) {
            const template = document.querySelector('#vacancy-item');
            const item = template.content.cloneNode(true);
            item.querySelector('.name').textContent = vacancy.name;
            item.querySelector('.level').textContent = vacancy.level;
            const requirementsContainer = item.querySelector('.requirements');
            vacancy.requirements.forEach(requirement => {
                const requirementItem = document.createElement("li");
                requirementItem.textContent = requirement;
                requirementsContainer.appendChild(requirementItem);
            });
            const offerContainer = item.querySelector('.offer');
            vacancy.offers.forEach(offer => {
                const offerItem = document.createElement("li");
                offerItem.textContent = offer;
                offerContainer.appendChild(offerItem);
            });
            return item;
        }
        function getItemContainer(button) {
            return button.parentNode.parentNode;
        }
        function attachVacancyListeners() {
            const vacancyItems = document.querySelectorAll('.vacancy-item .vacancy-item-short');
            attachEventsToList(vacancyItems, 'click', event => {
                vacancyItems.forEach(item => {
                    if (event.target !== item) {
                        getItemContainer(item).classList.remove(activeVacancyClass);
                    }
                });
                const container = getItemContainer(event.target);
                container.classList.toggle(activeVacancyClass);
            });
        }
        loadUrl('data/vacancies.json').then(
            data => {
                const vacancies = JSON.parse(data);
                redrawVacancies(vacancies);
                attachVacancyListeners();
            },
            error => {
                console.log('Could not load vacancies', error);
            }
        );
    }

    //People
    const peopleContainer = document.querySelector('.people-container');
    if (peopleContainer) {
        function redrawPeople(people) {
            peopleContainer.querySelectorAll('.people-list-item').forEach(item =>
                peopleContainer.removeChild(item)
            );
            people.reverse().forEach(person => {
                peopleContainer.firstChild.before( getPeopleItem(person) );
            });
        }
        function getPeopleItem(person) {
            const template = document.querySelector('#people-template');
            const item = template.content.cloneNode(true);
            let image = item.querySelector('img');
            image.setAttribute('src', person.image);
            image.setAttribute('alt', person.name);
            item.querySelector('.name').textContent = person.name;
            item.querySelector('.position').textContent = person.position;
            item.querySelector('.short-description').textContent = person.short_description;
            return item;
        }
        loadUrl('data/people.json').then(
            data => {
                const people = JSON.parse(data);
                redrawPeople(people);
            },
            error => {
                console.log('Could not load people', error);
            }
        );
    }

    // scroll
    const onListener = () => {
        const animationEls = document.querySelectorAll("main > .not-animated");
        if (animationEls.length === 0) {
            window.removeEventListener('scroll', onListener);
        }
        animationEls.forEach(el => {
            const animationOffset = el.getAttribute("data-animation-offset") || 0;
            const elOffset = el.offsetTop + el.parentNode.offsetTop;
            let windowBottomY = window.scrollY + window.innerHeight;
            if (windowBottomY > elOffset - animationOffset) {
                el.classList.remove('not-animated');
                el.classList.add('animated');
            }
        });
    };
    window.addEventListener('scroll', onListener);
    window.dispatchEvent(new Event('scroll'));
})();
