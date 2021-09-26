/*
 * Welcome to your app's main JavaScript file!
 *
 * We recommend including the built version of this JavaScript file
 * (and its CSS file) in your base layout (base.html.twig).
 */
import '@fortawesome/fontawesome-free/js/solid';
import '@fortawesome/fontawesome-free/js/brands';
import '@fortawesome/fontawesome-free/js/regular';
import '@fortawesome/fontawesome-free/js/fontawesome';

// any CSS you import will output into a single css file (app.css in this case)
import '../styles/app.scss';

// start the Stimulus application
import '../bootstrap';

document.addEventListener('turbo:before-cache', () => {
    document.querySelectorAll('[data-app-turbo-cache=show]').forEach(element => {
        element.classList.remove('d-none');
    });

    document.querySelectorAll('[data-app-turbo-cache=hide]').forEach(element => {
        element.classList.add('d-none');
    });
});