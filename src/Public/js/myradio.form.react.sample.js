import { h } from "preact";
import htm from "htm";

const html = htm.bind(h);

export default function Sample({ initialValue })
{
    return html`
    <b>Hello React! Initial value is ${initialValue}</b>
    `;
}
