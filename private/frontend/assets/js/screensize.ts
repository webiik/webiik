((): void => {
    function ratio(a: number, b: number): string {
        let ratio: string = '1:1';
        if (a > b) {
            ratio = (a / b).toFixed(1) + ':1';
        } else if (a < b) {
            ratio = '1:' + (b / a).toFixed(1);
        }
        return ratio;
    }

    const screenSizeElement: HTMLDivElement = document.createElement('div');
    screenSizeElement.setAttribute('id', 'screen-size');
    screenSizeElement.setAttribute('style', 'position: fixed; right: 0; bottom: 0; background: #000; color: #fff; padding: 10px; z-index: 99;');
    document.body.append(screenSizeElement);

    const screenSize = (): void => {
        const w: number = window.innerWidth;
        const h: number = window.innerHeight;
        const r: string = ratio(w, h);
        screenSizeElement.innerHTML = window.innerWidth + ' x ' + window.innerHeight + ', ' + r;
    };
    screenSize();
    window.addEventListener('resize', screenSize);
})();