/**
 * Get dependencies
 */
const {
	i18n: { __ },
	element: { Component, Fragment },
	components: { PanelBody, PanelRow, Spinner },
	plugins: { registerPlugin },
	editPost: { PluginSidebar, PluginSidebarMoreMenuItem },
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
    accounts: {},
    isLoading: true
  };

  async componentDidMount() {
    const accounts = await getAccounts();
    this.setState({
      accounts,
      isLoading: false
    });
  }

  render() {
		if (this.state.isLoading) {
      return (
        <p>
          <Spinner />
					{__("Loading accounts", "smolblog")}
        </p>
      );
		}

		/*
		const accountPanels = this.state.accounts.map( account => (
			<PanelBody title={account.name}></PanelBody>
		));
		*/

		console.log(this.state.accounts);

		return (
			<Fragment>
				<PluginSidebarMoreMenuItem target="smolblog-social">
					{__("Smolblog Social", "smolblog")}
				</PluginSidebarMoreMenuItem>
				<PluginSidebar
					name="smolblog-social"
					title={__("Smolblog Social", "smolblog")}
				>
					<PanelBody title={__("Sidebar Header", "smolblog")} opened>
						<PanelRow>
							<p>Nothing here.</p>
						</PanelRow>
					</PanelBody>
				</PluginSidebar>
			</Fragment>
		);
	}
};

registerPlugin("smolblog-social", {
  icon: "share-alt",
  render: SmolblogSocialSidebar
});
