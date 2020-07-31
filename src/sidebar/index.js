/**
 * Get dependencies
 */
const {
	i18n: { __ },
	element: { Component, Fragment },
	components: { PanelBody, PanelRow, Spinner, TextAreaControl },
	plugins: { registerPlugin },
	editPost: { PluginSidebar, PluginSidebarMoreMenuItem },
	data: { select, dispatch },
	apiFetch,
} = wp;

function getAccounts() {
  return apiFetch({
    path: "/smolblog/v1/social/accounts/"
  })
	.then(blockSetting => blockSetting)
	.catch(error => error);
}

class SmolblogSocialSidebar extends Component {
	state = {
		socialMeta: [],
    isLoading: true
	};
	
	setMeta( newMeta ) {
		this.setState({ socialMeta: newMeta });
		dispatch('core/editor').editPost({ meta: { smolblog_social_meta: JSON.stringify(newMeta) } });
	}

  async componentDidMount() {
		const accounts = await getAccounts();
		const pushAccounts = accounts.filter( account => account.push )

		// Get current post meta
		let currentMeta = [];
		try {
			currentMeta = JSON.parse(select('core/editor')
				.getEditedPostAttribute('meta')
				.smolblog_social_meta)
		}
		catch(error) {
			console.log('Error parsing JSON; assuming blank.', error);
		}

		console.log({ pushAccounts, currentMeta })

		// Disable all accounts in meta; we will re-enable valid accounts
		const socialMeta = currentMeta.map( account => {
			return {
				...account,
				disabled: true,
			}
		});

		// Enable or create meta for valid accounts
		pushAccounts.forEach( pushAccount => {
			const metaIndex = socialMeta.findIndex( metaAccount => metaAccount.account_id === pushAccount.account_id );
			if (metaIndex >= 0) {
				// Found account in existing meta; re-enable
				socialMeta[metaIndex].disabled = false;
			} else {
				// New account; create the metadata
				socialMeta.push({
					account_id: pushAccount.account_id,
					account_service: pushAccount.service,
					account_name: pushAccount.name
				});
			}
		});

		this.setMeta(socialMeta);

		this.setState({ isLoading: false });
  }

  render() {
		console.log(this.state);

		let accountPanels = (
			<p>
				<Spinner />
				{__("Loading accounts", "smolblog")}
			</p>
		);

		if (!this.state.isLoading) {
      accountPanels = this.state.socialMeta.map( account => (
				<PanelBody title={account.account_name} opened>
					<PanelRow>
						{/* <TextAreaControl
							label={__("Tweet text", "smolblog")}
						/> */}
						{account.disabled ? (<p>Push not enabled</p>) : (<p>TextArea goes here</p>)}
					</PanelRow>
				</PanelBody>
			));
		}

		return (
			<Fragment>
				<PluginSidebarMoreMenuItem target="smolblog-social">
					{__("Smolblog Social", "smolblog")}
				</PluginSidebarMoreMenuItem>
				<PluginSidebar
					name="smolblog-social"
					title={__("Smolblog Social", "smolblog")}
				>
					{accountPanels}
				</PluginSidebar>
			</Fragment>
		);
	}
};

registerPlugin("smolblog-social", {
  icon: "share-alt",
  render: SmolblogSocialSidebar
});
