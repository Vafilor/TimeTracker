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

// document.addEventListener('turbo:before-render', () => {
//     console.log('before render');
//
//     // document.documentElement.classList.add('auto-scroll');
//     // if(document.scrollingElement) {
//     //     document.scrollingElement.scrollTo(0, 0);
//     //     // document.documentElement.classList.remove('auto-scroll');
//     // }
// });

document.addEventListener('turbo:render', () => {
    console.log('render');

    document.documentElement.classList.add('auto-scroll');
    // if(document.scrollingElement) {
    //     document.scrollingElement.scrollTo(0, 0);
        // document.documentElement.classList.remove('auto-scroll');
    // }
});


// document.addEventListener('turbo:before-cache', () => {
//     console.log('turbo:before-cache');
// });
//
// document.addEventListener('turbo:click', (event: any) => {
//     console.log('turbo:click', event.detail.url);
// });