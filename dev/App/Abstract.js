import ko from 'ko';

import {
	$htmlCL,
	leftPanelDisabled,
	leftPanelType
} from 'Common/Globals';

import { KeyState } from 'Common/Enums';
import { rootAdmin, rootUser } from 'Common/Links';
import { initOnStartOrLangChange } from 'Common/Translator';

import LanguageStore from 'Stores/Language';
import ThemeStore from 'Stores/Theme';

import SaveTriggerComponent from 'Component/SaveTrigger';
import InputComponent from 'Component/Input';
import SelectComponent from 'Component/Select';
import TextAreaComponent from 'Component/TextArea';
import CheckboxMaterialDesignComponent from 'Component/MaterialDesign/Checkbox';
import CheckboxComponent from 'Component/Checkbox';

const Settings = rl.settings, doc = document;

export class AbstractApp {
	/**
	 * @param {RemoteStorage|AdminRemoteStorage} Remote
	 */
	constructor(Remote) {
		this.Remote = Remote;

		const refresh = (()=>dispatchEvent(new CustomEvent('rl.auto-logout-refresh'))).debounce(5000),
			fn = (ev=>{
				$htmlCL.toggle('rl-ctrl-key-pressed', ev.ctrlKey);
				refresh();
			}).debounce(500);

//		doc.addEventListener('touchstart', fn, {passive:true});
		['mousedown','keydown','keyup'/*,'mousemove'*/].forEach(t => doc.addEventListener(t, fn));

		shortcuts.add('escape,enter', '', KeyState.All, () => rl.Dropdowns.detectVisibility());
	}

	remote() {
		return null;
	}

	data() {
		return null;
	}

	/**
	 * @param {string} link
	 * @returns {boolean}
	 */
	download(link) {
		if (rl.settings.app('mobile')) {
			open(link, '_self');
			focus();
		} else {
			const oLink = doc.createElement('a');
			oLink.href = link;
			doc.body.appendChild(oLink).click();
			oLink.remove();
		}
		return true;
	}

	/**
	 * @param {string} token
	 */
	setClientSideToken(token) {
		rl.hash.set();
		Settings.set('AuthAccountHash', token);
	}

	logoutReload(close = false) {
		const logoutLink = rl.adminArea() ? rootAdmin() : rootUser();

		rl.hash.clear();
		close && window.close && window.close();

		if (location.href !== logoutLink) {
			setTimeout(() => (Settings.app('inIframe') ? parent : window).location.href = logoutLink, 100);
		} else {
			rl.route.reload();
		}
	}

	bootstart() {
		const mobile = Settings.app('mobile');

		ko.components.register('SaveTrigger', SaveTriggerComponent);
		ko.components.register('Input', InputComponent);
		ko.components.register('Select', SelectComponent);
		ko.components.register('TextArea', TextAreaComponent);
		ko.components.register('CheckboxSimple', CheckboxComponent);
		if (Settings.app('materialDesign') && !rl.settings.app('mobile')) {
			ko.components.register('Checkbox', CheckboxMaterialDesignComponent);
		} else {
			ko.components.register('Checkbox', CheckboxComponent);
		}

		initOnStartOrLangChange();

		if (!mobile) {
			// mobile
			window.addEventListener('resize', () => leftPanelDisabled(767 >= window.innerWidth));
		} else {
			$htmlCL.add('rl-mobile');
			leftPanelDisabled(true);
		}

		leftPanelDisabled.subscribe((bValue) => {
			$htmlCL.toggle('rl-left-panel-disabled', bValue);
			$htmlCL.toggle('rl-left-panel-enabled', !bValue);
		});

		leftPanelType.subscribe((sValue) => {
			$htmlCL.toggle('rl-left-panel-none', 'none' === sValue);
			$htmlCL.toggle('rl-left-panel-short', 'short' === sValue);
		});

		leftPanelDisabled.valueHasMutated();

		LanguageStore.populate();
		ThemeStore.populate();
	}

	/**
	 * @returns {void}
	 */
	hideLoading() {
		const id = id => doc.getElementById(id);
		id('rl-content').hidden = false;
		id('rl-loading').remove();
	}

}
