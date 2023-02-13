(() => {
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
    const formContainer = document.querySelector('.questions form');
    const stepsContainer = document.querySelector('.steps');
    const backdropContainer = document.querySelector('.backdrop');
    const processesContainer = document.querySelector('.processes');
    loadUrl('http://localhost/ccshomepage-develop/src/data/calculator.json').then((result) => { // ISPRAVITJ
        const data = JSON.parse(result);
        const form = createForm(formContainer, stepsContainer, backdropContainer, processesContainer);
        form.init(data.steps);
    }).catch(err => {
        console.error('Sorry can`t load calculator settings!', err);
    });

    function getStepName(step) {
        return step.name + (step.allowMultiple ? '[]' : '');
    }

    function createForm(formEl, stepsEl, backdropEl, processesEl) {
        const stepGenerator = {
            generateOption: (step, option) => {
                const optionTemplate = document.querySelector("#option-template")
                const optionEl = optionTemplate.content.cloneNode(true);
                const image = optionEl.querySelector('img');
                // Check for diff urls
                if(window.location.href != "http://localhost/ccshomepage-develop/src/calculator.html") {
                    image.setAttribute('src', 'ccshomepage-develop/src/assets/calculator/' + option.icon);
                }
                else {
                    image.setAttribute('src', 'assets/calculator/' + option.icon);
                }
                const id = step.name + '_' + option.name;
                const label = optionEl.querySelector('label')
                label.setAttribute('for', id);
                label.textContent = option.name;
                const input = optionEl.querySelector('input');
                input.setAttribute('name', getStepName(step));
                input.setAttribute('id', id);
                input.setAttribute('type', step.allowMultiple ? 'checkbox' : 'radio');
                input.setAttribute('value', option.value);
                optionEl.querySelector('.description').textContent = option.description || '';
                return optionEl;
            },
            generateChoose: step => {
                console.log('generate choose');
                const chooseTemplate = document.querySelector('#choose-template')
                const itemEl = chooseTemplate.content.cloneNode(true);
                const header = itemEl.querySelector("h2");
                header.textContent = step.title;
                const optionContainer = itemEl.querySelector('.option-list')
                step.options.forEach(option => {
                    const optionEl = stepGenerator.generateOption(step, option);
                    optionContainer.append(optionEl);
                    optionEl.addEventListener('click', () => {

                    });
                });
                return itemEl;
            },
            generateInput: () => {
                console.log('generate input');
            }
        };
        const defaultGenerator = "generateInput";
        function renderForm(formStepsEl, steps) {
            formStepsEl.textContent = '';
            const openEstimateTemplate = document.querySelector("#open-estimate-template");
            const estimateEl = openEstimateTemplate.content.cloneNode(true);
            steps.forEach(step => {
                formStepsEl.append(renderFormStep(step));
            });
            formStepsEl.append(estimateEl);
        }
        function renderFormStep(step) {
            const generatorName = "generate" + step.type.charAt(0).toUpperCase() + step.type.slice(1);
            return (stepGenerator[generatorName] || stepGenerator[defaultGenerator])(step);
        }

        function renderSteps(stepsListEl, steps) {
            stepsListEl.textContent = '';
            steps.forEach(step => {
                stepsListEl.append(renderStep(step));
            });
        }
        function renderStep(stepData) {
            const stepEl = document.createElement('li');
            stepEl.textContent = stepData.short_title;
            return stepEl;
        }

        const styleClasses = {
            passed: 'passed',
            selected: 'selected',
            hidden: 'hidden',
        }
        const selectabeTypes = ['radio', 'checkbox'];
        // Show old results,if not default calculator url detected.
        if(window.location.href != "http://localhost/ccshomepage-develop/src/calculator.html") {
            var revisitData = document.getElementById("revisit_array").value;
            const convertedData = JSON.parse(revisitData);
            processesEl.querySelector('.planning-step .process-step-date').textContent = convertedData.planning;
            processesEl.querySelector('.design-step .process-step-date').textContent = convertedData.design;
            processesEl.querySelector('.development-step .process-step-date').textContent = convertedData.development;
            processesEl.querySelector('.testing-step .process-step-date').textContent = convertedData.testing;
            processesEl.querySelector('.launch-step .process-step-date').textContent = convertedData.launch;
            document.getElementById("total_coast").innerHTML = "Total : "+convertedData.estimateCost+ "$";
        }

        return {

            steps: [],
            currentStep: 0,
            init: function(steps) {
                this.steps = steps;
                this.formEl = formEl;
                this.stepsEl = stepsEl;
                this.backdropEl = backdropEl;
                this.processesEl = processesEl;
                renderSteps(stepsEl.querySelector('ul'), steps);
                renderForm(formEl.querySelector('.form-steps'), steps);
                this.attachHandlers();
                this.switchStep(1, false);
            },
            attachHandlers: function() {
                this.attachFormListeners(this.formEl);
                this.attachStepsListeners(this.stepsEl);
            },
            attachFormListeners: function() { //make private?
                this.formEl.querySelector('.next-question').addEventListener('click', e => {
                    e.preventDefault();
                    this.nextStep();
                    
                });
                this.formEl.querySelector('button[type="submit"]').addEventListener('click', e => {
                   e.preventDefault();
                   this.calculate();
                });
                this.formEl.querySelector('.estimate').addEventListener('click', e => {
                    e.preventDefault();
                    console.log('clicked estimate: ', this.stepsEl );
                    this.stepsEl.classList.add('visible-dialog');
                    this.showBackdrop();
                });
                this.stepsEl.querySelector('.close-steps').addEventListener('click', e => {
                    e.preventDefault();
                    this.stepsEl.classList.remove('visible-dialog');
                    this.hideBackdrop();
                });
                this.formEl.querySelector('.timeline-btn').addEventListener('click', e => {
                    e.preventDefault();
                    this.processesEl.classList.add('visible-dialog');
                    this.showBackdrop();
                });
                this.processesEl.querySelector('.close-processes').addEventListener('click', e => {
                    e.preventDefault();
                    this.processesEl.classList.remove('visible-dialog');
                    this.hideBackdrop();
                });
                let listEl = this.stepsEl.querySelector('ul');
                listEl.addEventListener('click', e => {
                    if (e.target.classList.contains(styleClasses.passed)) {
                        console.log(listEl.childNodes, listEl.children);
                        const index = Array.prototype.slice.call(listEl.childNodes).findIndex(item => item === e.target);
                        this.switchStep(index + 1, false);
                    }
                });
            },
            attachStepsListeners: function() {
                this.stepsEl.querySelectorAll('li').forEach((stepEl, index) => {
                    if (stepEl.classList.contains(styleClasses.passed) && !stepEl.classList.contains(styleClasses.selected)) {
                        this.switchStep(index, false);
                    }
                });
            },
            nextStep: function() {
                const nextStep = this.currentStep + 1;
                this.switchStep(nextStep);
            },
            prevStep: function() {
                const prevStepIdx = this.currentStep - 1;
                this.switchStep(prevStepIdx, false);
            },
            switchStep: function(step, validate = true) {
                if (this.isValidStepIndex(step)) {
                    this.showError("Not valida step number");
                    return;
                }
                if (validate && !this.validateStep(this.currentStep)) {
                    this.showError('Please fill information!');
                    return;
                }
                this.currentStep = step;
                this.showStep();
                this.updateButtons();
                this.updateSteps(validate);
            },
            showStep: function() {
                this.formEl.querySelectorAll('.form-step').forEach(formStepEl => {
                    formStepEl.classList.add(styleClasses.hidden);
                })
                const nextStep = this.formEl.querySelector('.form-step:nth-child(' + (this.currentStep) + ')');
                nextStep.classList.remove(styleClasses.hidden);
            },
            updateButtons: function() {
                if (this.isLastStep(this.currentStep)) {
                    this.formEl.querySelector('.next-question').classList.add('hidden');
                    this.formEl.querySelector('.timeline-btn').classList.add('hidden');
                    this.formEl.querySelector('.submit').classList.remove('hidden');
                } else {
                    this.formEl.querySelector('.next-question').classList.remove('hidden');
                    this.formEl.querySelector('.submit').classList.add('hidden');
                }
            },
            updateSteps: function(validated) {
                const step = this.stepsEl.querySelector('li.selected');
                if (step) {
                    step.classList.remove('selected');
                    if (validated) {
                        step.classList.add('passed');
                    }
                }
                const newStep = this.stepsEl.querySelector('li:nth-child(' + (this.currentStep) + ')');
                newStep.classList.add('selected');
            },
            isLastStep: function(step) {
                return step >= this.getAmountOfSteps();
            },
            getAmountOfSteps: function() {
                return this.steps.length;
            },
            isValidStepIndex: function(step) {
                return step > this.getAmountOfSteps() || step <= 0;
            },
            validateStep: function(step) {
                const stepData = this.steps[step - 1];
                const input = this.formEl.querySelectorAll('input[name="' + getStepName(stepData) + '"]');
                return Array.prototype.slice.call(input).find(el => {
                    const elType = el.getAttribute('type');
                    return selectabeTypes.includes(elType) ? el.checked : !!el.value;
                });
            },
            showError: function(text) {
                console.error("ShowError: ", text);
            },
            calculate: function() {
                    const data = new FormData(this.formEl);
                    var req = new XMLHttpRequest();
                        let object = {};
                        data.forEach((value, key) => {
                          if (!Reflect.has(object, key)) {
                            object[key] = value;
                            return;
                          }
                          if (!Array.isArray(object[key])) {
                            object[key] = [object[key]];
                          }
                          object[key].push(value);
                        });
                        console.log(Object.values(object));
                           var theUrl = "http://localhost/json/save";
                            req.open("POST", theUrl);
                            req.setRequestHeader("Content-Type", "application/x-www-form-urlencoded;charset=UTF-8");
                            req.send('test='+Object.values(object)); // dostatj dannie
                req.onreadystatechange = () => {
                    if (req.readyState === XMLHttpRequest.DONE && req.status === 200) {
                        const calculation = JSON.parse(req.responseText);
                        console.error("ShowError1: ", calculation);
                        console.error("ShowError2: ", req.responseText);
                        console.error("ShowError3: ", req.readyState);
                        this.showResults(calculation);
                    }
                };
            },
            showResults: function(data) {
                console.error("Data: ", data);
                if (!data) return;

                processesEl.querySelector('.planning-step .process-step-date').textContent = data.planning;
                processesEl.querySelector('.design-step .process-step-date').textContent = data.design;
                processesEl.querySelector('.development-step .process-step-date').textContent = data.development;
                processesEl.querySelector('.testing-step .process-step-date').textContent = data.testing;
                processesEl.querySelector('.launch-step .process-step-date').textContent = data.launch;
                document.getElementById("total_coast").innerHTML = "Total : "+data.estimateCost+ "$";
            },
            showBackdrop: function(){
                this.backdropEl.classList.add('visible-dialog');
                const root = document.getElementsByTagName( 'html' )[0];
                root.classList.add('visible-dialog')
            },
            hideBackdrop: function(){
                this.backdropEl.classList.remove('visible-dialog');
                const root = document.getElementsByTagName( 'html' )[0];
                root.classList.remove('visible-dialog')
            }
        };
    }

})();
