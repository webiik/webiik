import * as React from 'react';
import './meow.scss';

export const Meow = (props: { t: any }) => {

    const createMarkup = (html: string) => {
        return {__html: html};
    }

    return (
        <div className="meow">
            <h1>{props.t.h1}</h1>
            <p dangerouslySetInnerHTML={createMarkup(props.t.txt)}/>
        </div>
    );
};