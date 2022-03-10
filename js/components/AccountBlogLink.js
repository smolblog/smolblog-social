export const AccountBlogLink = (props) => {
  const {
    id,
    social_type: type = "",
    social_username: name = "",
    additional_info: info = "",
    can_push: push = false,
    can_pull: pull = false,
  } = props.account;

  return (
    <tr>
      <td className={type}>
        {name} <span className="additional-info">{info}</span>
      </td>
      <td>{yesOrNo(push)}</td>
      <td>{yesOrNo(pull)}</td>
    </tr>
  );
};

const yesOrNo = (check) => {
  return check && check > 0 ? (
    <span style={{ color: "green", "font-weight": "bold" }}>Yes</span>
  ) : (
    <span style={{ color: "red", "font-weight": "bold" }}>No</span>
  );
};
