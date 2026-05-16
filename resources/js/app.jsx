import './bootstrap';
import React from 'react';
import { createRoot } from 'react-dom/client';
import LandingPage from './components/LandingPage';

const rootElement = document.getElementById('react-landing-page');

if (rootElement) {
    const props = Object.assign({}, rootElement.dataset);
    const root = createRoot(rootElement);
    root.render(<LandingPage loginUrl={props.loginUrl} />);
}