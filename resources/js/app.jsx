import './bootstrap';
import React from 'react';
import { createRoot } from 'react-dom/client';
import LandingPage from './components/LandingPage';
import SynkoraDashboard from './components/SynkoraDashboard';

const landingElement = document.getElementById('react-landing-page');
if (landingElement) {
    const props = Object.assign({}, landingElement.dataset);
    createRoot(landingElement).render(<LandingPage loginUrl={props.loginUrl} />);
}

const dashboardElement = document.getElementById('react-app');
if (dashboardElement) {
    const props = Object.assign({}, dashboardElement.dataset);
    const results = props.results ? JSON.parse(props.results) : [];
    const allAssets = props.allAssets ? JSON.parse(props.allAssets) : [];
    const chatMessages = props.chatMessages ? JSON.parse(props.chatMessages) : [];

    createRoot(dashboardElement).render(
        <SynkoraDashboard 
            csrfToken={props.csrfToken}
            initialQuery={props.query} 
            results={results}
            allAssets={allAssets}
            chatMessages={chatMessages}
            googleToken={props.googleToken}
            apiKey={props.apiKey}
            clientId={props.clientId}
        />
    );
}